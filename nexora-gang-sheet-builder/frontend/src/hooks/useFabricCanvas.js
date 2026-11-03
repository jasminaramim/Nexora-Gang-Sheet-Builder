import { useEffect, useRef, useState, useCallback } from 'react';
import { fabric } from 'fabric';
import { calculateDpi, getDpiQuality } from '../utils/dpiChecker';

export function useFabricCanvas({
    sheetWidth,
    sheetHeight,
    unit = 'in',
    margin = 0.25,
    onSelectionChange,
    onCanvasModified,
}) {
    const canvasRef = useRef(null);
    const fabricInstance = useRef(null);
    const clipboard = useRef(null);

    // Zoom and pan state
    const [zoom, setZoom] = useState(1);
    const [history, setHistory] = useState([]);
    const [historyIndex, setHistoryIndex] = useState(-1);
    const [selectedObject, setSelectedObject] = useState(null);
    const [layers, setLayers] = useState([]);
    const isHistoryUpdating = useRef(false);

    // Pixels per unit conversion for canvas rendering viewport
    // E.g. at 20 px per inch, a 22" x 60" canvas = 440px x 1200px (comfortable viewport size)
    const PPI = 25; 
    const canvasPixelWidth = Math.round(sheetWidth * PPI);
    const canvasPixelHeight = Math.round(sheetHeight * PPI);

    // Update layers list from canvas
    const updateLayersList = useCallback(() => {
        if (!fabricInstance.current) return;
        const objects = fabricInstance.current.getObjects();
        const items = objects.map((obj, idx) => {
            const widthIn = (obj.width * (obj.scaleX || 1)) / PPI;
            const heightIn = (obj.height * (obj.scaleY || 1)) / PPI;
            const naturalW = obj._element ? obj._element.naturalWidth : (obj.naturalWidth || 1000);
            const naturalH = obj._element ? obj._element.naturalHeight : (obj.naturalHeight || 1000);
            const dpi = calculateDpi(naturalW, naturalH, widthIn, heightIn);
            const quality = getDpiQuality(dpi);

            return {
                id: obj.id || `layer_${idx}`,
                index: idx,
                name: obj.name || `Design ${idx + 1}`,
                width: Math.round(widthIn * 10) / 10,
                height: Math.round(heightIn * 10) / 10,
                dpi,
                quality,
                locked: obj.lockMovementX || false,
                visible: obj.visible !== false,
                thumb: obj.toDataURL ? obj.toDataURL({ format: 'png', multiplier: 0.2 }) : '',
                ref: obj,
            };
        });
        setLayers(items.reverse()); // Top layer first
    }, [PPI]);

    // Push canvas state to history stack
    const saveStateToHistory = useCallback(() => {
        if (!fabricInstance.current || isHistoryUpdating.current) return;
        const json = fabricInstance.current.toJSON(['id', 'name', 'naturalWidth', 'naturalHeight', 'lockMovementX', 'lockMovementY', 'lockScalingX', 'lockScalingY', 'lockRotation']);
        
        setHistory((prev) => {
            const updated = prev.slice(0, historyIndex + 1);
            return [...updated, json].slice(-30); // max 30 steps
        });
        setHistoryIndex((prev) => Math.min(prev + 1, 29));

        updateLayersList();
        if (onCanvasModified) {
            onCanvasModified();
        }
    }, [historyIndex, updateLayersList, onCanvasModified]);

    // Initialize Fabric Canvas
    useEffect(() => {
        if (!canvasRef.current) return;

        // Custom purple styling for controls
        fabric.Object.prototype.transparentCorners = false;
        fabric.Object.prototype.cornerColor = '#7c3aed';
        fabric.Object.prototype.cornerStrokeColor = '#ffffff';
        fabric.Object.prototype.borderColor = '#8b5cf6';
        fabric.Object.prototype.cornerSize = 10;
        fabric.Object.prototype.cornerStyle = 'circle';
        fabric.Object.prototype.borderDashArray = [4, 4];
        fabric.Object.prototype.borderScaleFactor = 2;

        const canvas = new fabric.Canvas(canvasRef.current, {
            width: canvasPixelWidth,
            height: canvasPixelHeight,
            backgroundColor: '',
            preserveObjectStacking: true,
            selection: true,
        });

        fabricInstance.current = canvas;

        // Event listeners
        const handleSelection = () => {
            const active = canvas.getActiveObject();
            setSelectedObject(active);
            if (onSelectionChange) {
                onSelectionChange(active);
            }
        };

        canvas.on('selection:created', handleSelection);
        canvas.on('selection:updated', handleSelection);
        canvas.on('selection:cleared', () => {
            setSelectedObject(null);
            if (onSelectionChange) onSelectionChange(null);
        });

        canvas.on('object:modified', () => {
            saveStateToHistory();
        });

        canvas.on('object:added', () => {
            updateLayersList();
        });

        canvas.on('object:removed', () => {
            updateLayersList();
        });

        // Initial empty state
        saveStateToHistory();

        return () => {
            canvas.dispose();
        };
    }, []); // eslint-disable-line

    // Handle Sheet Dimension Changes
    useEffect(() => {
        if (!fabricInstance.current) return;
        fabricInstance.current.setWidth(canvasPixelWidth);
        fabricInstance.current.setHeight(canvasPixelHeight);
        fabricInstance.current.renderAll();
    }, [canvasPixelWidth, canvasPixelHeight]);

    // Add Image to Canvas
    const addImageToCanvas = useCallback((imageUrl, naturalW = 1000, naturalH = 1000, defaultWidthInches = 10) => {
        if (!fabricInstance.current) return;

        fabric.Image.fromURL(imageUrl, (img) => {
            if (!img) return;

            // Target physical width on canvas in pixels
            const targetPixelWidth = Math.min(
                defaultWidthInches * PPI,
                fabricInstance.current.getWidth() - (margin * 2 * PPI)
            );
            const scale = targetPixelWidth / (img.width || 1);

            img.set({
                id: `design_${Date.now()}_${Math.floor(Math.random() * 1000)}`,
                left: Math.round(fabricInstance.current.getWidth() / 2 - (img.width * scale) / 2),
                top: Math.round(fabricInstance.current.getHeight() / 2 - (img.height * scale) / 2),
                scaleX: scale,
                scaleY: scale,
                naturalWidth: naturalW,
                naturalHeight: naturalH,
            });

            fabricInstance.current.add(img);
            fabricInstance.current.setActiveObject(img);
            fabricInstance.current.renderAll();
            saveStateToHistory();
        }, { crossOrigin: 'anonymous' });
    }, [PPI, margin, saveStateToHistory]);

    // Undo & Redo
    const undo = useCallback(() => {
        if (historyIndex > 0 && fabricInstance.current) {
            isHistoryUpdating.current = true;
            const nextIndex = historyIndex - 1;
            fabricInstance.current.loadFromJSON(history[nextIndex], () => {
                fabricInstance.current.renderAll();
                setHistoryIndex(nextIndex);
                isHistoryUpdating.current = false;
                updateLayersList();
            });
        }
    }, [history, historyIndex, updateLayersList]);

    const redo = useCallback(() => {
        if (historyIndex < history.length - 1 && fabricInstance.current) {
            isHistoryUpdating.current = true;
            const nextIndex = historyIndex + 1;
            fabricInstance.current.loadFromJSON(history[nextIndex], () => {
                fabricInstance.current.renderAll();
                setHistoryIndex(nextIndex);
                isHistoryUpdating.current = false;
                updateLayersList();
            });
        }
    }, [history, historyIndex, updateLayersList]);

    // Copy & Paste
    const copy = useCallback(() => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (active) {
            active.clone((cloned) => {
                clipboard.current = cloned;
            });
        }
    }, []);

    const paste = useCallback(() => {
        if (!fabricInstance.current || !clipboard.current) return;
        clipboard.current.clone((clonedObj) => {
            fabricInstance.current.discardActiveObject();
            clonedObj.set({
                left: clonedObj.left + 15,
                top: clonedObj.top + 15,
                evented: true,
                id: `design_${Date.now()}`,
            });
            if (clonedObj.type === 'activeSelection') {
                clonedObj.canvas = fabricInstance.current;
                clonedObj.forEachObject((obj) => {
                    fabricInstance.current.add(obj);
                });
                clonedObj.setCoords();
            } else {
                fabricInstance.current.add(clonedObj);
            }
            fabricInstance.current.setActiveObject(clonedObj);
            fabricInstance.current.requestRenderAll();
            saveStateToHistory();
        });
    }, [saveStateToHistory]);

    // Duplicate
    const duplicate = useCallback(() => {
        copy();
        paste();
    }, [copy, paste]);

    // Delete selected
    const deleteSelected = useCallback(() => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (!active) return;

        if (active.type === 'activeSelection') {
            active.forEachObject((obj) => {
                fabricInstance.current.remove(obj);
            });
        } else {
            fabricInstance.current.remove(active);
        }
        fabricInstance.current.discardActiveObject();
        fabricInstance.current.renderAll();
        saveStateToHistory();
    }, [saveStateToHistory]);

    // Clear Canvas
    const clearCanvas = useCallback(() => {
        if (!fabricInstance.current) return;
        if (window.confirm('Clear all designs from this gang sheet?')) {
            fabricInstance.current.clear();
            saveStateToHistory();
        }
    }, [saveStateToHistory]);

    // Align Tools
    const alignObject = useCallback((alignment) => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (!active) return;

        const canvasW = fabricInstance.current.getWidth();
        const canvasH = fabricInstance.current.getHeight();
        const bound = active.getBoundingRect();

        switch (alignment) {
            case 'left':
                active.set({ left: margin * PPI });
                break;
            case 'center_h':
                active.set({ left: canvasW / 2 - bound.width / 2 });
                break;
            case 'right':
                active.set({ left: canvasW - (margin * PPI) - bound.width });
                break;
            case 'top':
                active.set({ top: margin * PPI });
                break;
            case 'center_v':
                active.set({ top: canvasH / 2 - bound.height / 2 });
                break;
            case 'bottom':
                active.set({ top: canvasH - (margin * PPI) - bound.height });
                break;
            default:
                break;
        }

        active.setCoords();
        fabricInstance.current.renderAll();
        saveStateToHistory();
    }, [PPI, margin, saveStateToHistory]);

    // Flip H & V
    const flipHorizontal = useCallback(() => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (active) {
            active.set('flipX', !active.flipX);
            fabricInstance.current.renderAll();
            saveStateToHistory();
        }
    }, [saveStateToHistory]);

    const flipVertical = useCallback(() => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (active) {
            active.set('flipY', !active.flipY);
            fabricInstance.current.renderAll();
            saveStateToHistory();
        }
    }, [saveStateToHistory]);

    // Rotate 90 deg
    const rotate90 = useCallback(() => {
        if (!fabricInstance.current) return;
        const active = fabricInstance.current.getActiveObject();
        if (active) {
            const currentAngle = active.angle || 0;
            active.rotate((currentAngle + 90) % 360);
            active.setCoords();
            fabricInstance.current.renderAll();
            saveStateToHistory();
        }
    }, [saveStateToHistory]);

    // Lock / Unlock Layer
    const toggleLock = useCallback((object) => {
        if (!object) return;
        const isLocked = !object.lockMovementX;
        object.set({
            lockMovementX: isLocked,
            lockMovementY: isLocked,
            lockScalingX: isLocked,
            lockScalingY: isLocked,
            lockRotation: isLocked,
            hasControls: !isLocked,
        });
        fabricInstance.current?.renderAll();
        updateLayersList();
    }, [updateLayersList]);

    // Keyboard Shortcuts
    useEffect(() => {
        const handleKeyDown = (e) => {
            // Ignore if typing inside an input or textarea
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                deleteSelected();
            } else if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
                copy();
            } else if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                paste();
            } else if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                if (e.shiftKey) redo();
                else undo();
            } else if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
                redo();
            } else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                // Nudge selected object
                const active = fabricInstance.current?.getActiveObject();
                if (active) {
                    e.preventDefault();
                    const step = e.shiftKey ? 10 : 2;
                    if (e.key === 'ArrowUp') active.top -= step;
                    if (e.key === 'ArrowDown') active.top += step;
                    if (e.key === 'ArrowLeft') active.left -= step;
                    if (e.key === 'ArrowRight') active.left += step;
                    active.setCoords();
                    fabricInstance.current.renderAll();
                    saveStateToHistory();
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [deleteSelected, copy, paste, undo, redo, saveStateToHistory]);

    return {
        canvasRef,
        fabricInstance,
        zoom,
        setZoom,
        layers,
        selectedObject,
        PPI,
        canvasPixelWidth,
        canvasPixelHeight,
        addImageToCanvas,
        undo,
        redo,
        canUndo: historyIndex > 0,
        canRedo: historyIndex < history.length - 1,
        copy,
        paste,
        duplicate,
        deleteSelected,
        clearCanvas,
        alignObject,
        flipHorizontal,
        flipVertical,
        rotate90,
        toggleLock,
        updateLayersList,
        saveStateToHistory,
    };
}

