import React from 'react';
import {
    ZoomIn,
    ZoomOut,
    Maximize,
    RotateCcw,
    Undo2,
    Redo2,
    Trash2,
    Copy,
    FlipHorizontal,
    FlipVertical,
    RotateCw,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignStartVertical,
    AlignCenterVertical,
    AlignEndVertical,
    Lock,
    Unlock,
} from 'lucide-react';

export default function CanvasToolbar({
    zoom,
    onZoomIn,
    onZoomOut,
    onZoomFit,
    onZoomReset,
    canUndo,
    canRedo,
    onUndo,
    onRedo,
    onClear,
    onDuplicate,
    onDelete,
    onFlipH,
    onFlipV,
    onRotate90,
    onAlign,
    selectedObject,
    onToggleLock,
}) {
    const hasSelection = !!selectedObject;
    const isLocked = selectedObject?.lockMovementX;

    return (
        <div className="canvas-top-toolbar">
            {/* Left: History & Clear */}
            <div className="toolbar-group">
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onUndo}
                    disabled={!canUndo}
                    title="Undo (Ctrl+Z)"
                >
                    <Undo2 size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onRedo}
                    disabled={!canRedo}
                    title="Redo (Ctrl+Y)"
                >
                    <Redo2 size={16} />
                </button>
                <div className="tool-divider" />
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onClear}
                    title="Clear All Objects"
                >
                    <Trash2 size={16} color="#ef4444" />
                </button>
            </div>

            {/* Center: Transform & Align Tools */}
            <div className="toolbar-group">
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onDuplicate}
                    disabled={!hasSelection}
                    title="Duplicate Selected (Ctrl+C, Ctrl+V)"
                >
                    <Copy size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onDelete}
                    disabled={!hasSelection}
                    title="Delete Selected (Backspace / Delete)"
                >
                    <Trash2 size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onToggleLock(selectedObject)}
                    disabled={!hasSelection}
                    title={isLocked ? "Unlock Object" : "Lock Object"}
                >
                    {isLocked ? <Lock size={16} color="#f59e0b" /> : <Unlock size={16} />}
                </button>

                <div className="tool-divider" />

                <button
                    type="button"
                    className="tool-btn"
                    onClick={onFlipH}
                    disabled={!hasSelection}
                    title="Flip Horizontally"
                >
                    <FlipHorizontal size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onFlipV}
                    disabled={!hasSelection}
                    title="Flip Vertically"
                >
                    <FlipVertical size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onRotate90}
                    disabled={!hasSelection}
                    title="Rotate 90° Clockwise"
                >
                    <RotateCw size={16} />
                </button>

                <div className="tool-divider" />

                {/* Alignment */}
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('left')}
                    disabled={!hasSelection}
                    title="Align Left Margin"
                >
                    <AlignLeft size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('center_h')}
                    disabled={!hasSelection}
                    title="Center Horizontally"
                >
                    <AlignCenter size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('right')}
                    disabled={!hasSelection}
                    title="Align Right Margin"
                >
                    <AlignRight size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('top')}
                    disabled={!hasSelection}
                    title="Align Top Margin"
                >
                    <AlignStartVertical size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('center_v')}
                    disabled={!hasSelection}
                    title="Center Vertically"
                >
                    <AlignCenterVertical size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={() => onAlign('bottom')}
                    disabled={!hasSelection}
                    title="Align Bottom Margin"
                >
                    <AlignEndVertical size={16} />
                </button>
            </div>

            {/* Right: Zoom Tools */}
            <div className="toolbar-group">
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onZoomOut}
                    title="Zoom Out"
                >
                    <ZoomOut size={16} />
                </button>
                <span style={{ fontSize: '12px', fontWeight: 600, color: '#94a3b8', minWidth: '40px', textAlign: 'center' }}>
                    {Math.round(zoom * 100)}%
                </span>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onZoomIn}
                    title="Zoom In"
                >
                    <ZoomIn size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onZoomFit}
                    title="Zoom to Fit Viewport"
                >
                    <Maximize size={16} />
                </button>
                <button
                    type="button"
                    className="tool-btn"
                    onClick={onZoomReset}
                    title="Reset Zoom (100%)"
                >
                    <RotateCcw size={16} />
                </button>
            </div>
        </div>
    );
}

