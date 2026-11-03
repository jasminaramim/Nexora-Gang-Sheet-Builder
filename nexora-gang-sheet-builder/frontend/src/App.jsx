import React, { useState, useEffect, useCallback } from 'react';
import confetti from 'canvas-confetti';
import TopNav from './components/TopNav/TopNav';
import LeftSidebar from './components/LeftSidebar/LeftSidebar';
import CanvasToolbar from './components/CenterCanvas/CanvasToolbar';
import CanvasWorkspace from './components/CenterCanvas/CanvasWorkspace';
import RightSidebar from './components/RightSidebar/RightSidebar';
import BottomToolbar from './components/BottomBar/BottomToolbar';
import ExportModal from './components/Modals/ExportModal';
import PreviewModal from './components/Modals/PreviewModal';
import MyDesignsModal from './components/Modals/MyDesignsModal';

import { useFabricCanvas } from './hooks/useFabricCanvas';
import {
    nexoraConfig,
    fetchBuilderConfig,
    fetchProducts,
    saveGangSheet,
    addToCartAndCheckout,
} from './utils/api';
import './styles/builder.css';

export default function App({ initialProductId = 0, initialVariationId = 0, initialWidth = 22, initialHeight = 60, initialToken = '' }) {
    // Sheets state (Multiple Sheet Support)
    const [sheets, setSheets] = useState([
        { id: 1, name: 'Sheet 1', width: initialWidth, height: initialHeight },
    ]);
    const [activeSheetIndex, setActiveSheetIndex] = useState(0);

    const activeSheet = sheets[activeSheetIndex] || { width: 22, height: 60 };
    const sheetWidth = activeSheet.width;
    const sheetHeight = activeSheet.height;

    // Workspace & settings state
    const [unit, setUnit] = useState('in');
    const [margin, setMargin] = useState(0.25);
    const [canvasBg, setCanvasBg] = useState('checkerboard');
    const [presets, setPresets] = useState(nexoraConfig.presets || []);
    const [pricingConfig, setPricingConfig] = useState(nexoraConfig.pricing || {});
    const [products, setProducts] = useState([]);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [selectedVariation, setSelectedVariation] = useState(null);
    const [quantity, setQuantity] = useState(1);

    // Media library
    const [uploadedMedia, setUploadedMedia] = useState([
        {
            id: 'sample_1',
            name: 'Neon Cyber Skull (Sample DTF)',
            url: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
            width: 1200,
            height: 1200,
            dpi: 300,
        },
        {
            id: 'sample_2',
            name: 'Vintage Eagle Emblem (Sample)',
            url: 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=800&auto=format&fit=crop&q=80',
            width: 1500,
            height: 1200,
            dpi: 300,
        }
    ]);

    // Pricing & Utilization stats (Dynamic from WooCommerce, no hardcoded price)
    const [totalPrice, setTotalPrice] = useState(0);
    const [utilization, setUtilization] = useState(0);
    const [usedArea, setUsedArea] = useState(0);
    const [wastedArea, setWastedArea] = useState(0);

    // Modals
    const [isExportModalOpen, setIsExportModalOpen] = useState(false);
    const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
    const [isMyDesignsModalOpen, setIsMyDesignsModalOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Helper to find matching variation by width & height
    const findMatchingVariation = useCallback((product, w, h) => {
        if (!product || !product.is_variable || !product.variations || product.variations.length === 0) return null;
        const roundW = Math.round(w);
        const roundH = Math.round(h);
        
        // 1. Try parsed dimensions match
        const exact = product.variations.find(
            (v) => Math.round(v.parsed_width) === roundW && Math.round(v.parsed_height) === roundH
        );
        if (exact) return exact;

        // 2. Try attribute string match
        const strMatch = product.variations.find((v) => {
            const attrStr = JSON.stringify(v.attributes).toLowerCase();
            return attrStr.includes(`${roundW}`) && attrStr.includes(`${roundH}`);
        });
        if (strMatch) return strMatch;

        return null;
    }, []);

    // Dynamic Pricing Engine: Fetches real WooCommerce product / variation price
    const calculatePricing = useCallback((width, height, variation = selectedVariation, product = selectedProduct, qty = quantity) => {
        let base = 0;
        if (variation && variation.price > 0) {
            base = variation.price;
        } else if (product && !product.is_variable && product.price > 0) {
            base = product.price;
        } else {
            const totalSqIn = width * height;
            const totalSqFt = totalSqIn / 144.0;
            const mode = pricingConfig.pricing_mode || 'tiered';

            if (mode === 'tiered' && pricingConfig.tiered_pricing) {
                const matchTier = pricingConfig.tiered_pricing.find(
                    (t) => totalSqFt >= t.min_sqft && totalSqFt <= t.max_sqft
                );
                const rate = matchTier ? matchTier.rate : (pricingConfig.price_per_sq_ft || 7.50);
                base = Math.max(pricingConfig.min_price || 15.00, totalSqFt * rate);
            } else if (mode === 'sq_ft') {
                const rate = pricingConfig.price_per_sq_ft || 7.50;
                base = Math.max(pricingConfig.min_price || 15.00, totalSqFt * rate);
            } else if (mode === 'sq_in') {
                const rate = pricingConfig.price_per_sq_in || 0.052;
                base = Math.max(pricingConfig.min_price || 15.00, totalSqIn * rate);
            } else {
                const match = presets.find((p) => p.width === width && p.height === height);
                base = match ? match.price : (totalSqFt * 7.50);
            }
        }

        const finalTotal = Math.max(0, base) * Math.max(1, qty);
        return Math.round(finalTotal * 100) / 100;
    }, [pricingConfig, presets, selectedVariation, selectedProduct, quantity]);

    // Recalculate stats when canvas objects are added or moved
    const handleCanvasModified = useCallback(() => {
        if (!fabricCanvas.fabricInstance.current) return;
        const objects = fabricCanvas.fabricInstance.current.getObjects();
        const totalSheetArea = sheetWidth * sheetHeight;

        let totalUsedArea = 0;
        objects.forEach((obj) => {
            const w = (obj.width * (obj.scaleX || 1)) / fabricCanvas.PPI;
            const h = (obj.height * (obj.scaleY || 1)) / fabricCanvas.PPI;
            totalUsedArea += w * h;
        });

        const rate = totalSheetArea > 0 ? Math.min(100, Math.round((totalUsedArea / totalSheetArea) * 1000) / 10) : 0;
        setUtilization(rate);
        setUsedArea(Math.round(totalUsedArea * 10) / 10);
        setWastedArea(Math.max(0, Math.round((totalSheetArea - totalUsedArea) * 10) / 10));

        const price = calculatePricing(sheetWidth, sheetHeight, selectedVariation, selectedProduct, quantity);
        setTotalPrice(price);
    }, [sheetWidth, sheetHeight, calculatePricing, selectedVariation, selectedProduct, quantity]);

    // Fabric Canvas Hook
    const fabricCanvas = useFabricCanvas({
        sheetWidth,
        sheetHeight,
        unit,
        margin,
        onCanvasModified: handleCanvasModified,
    });

    // Load initial config & products from WooCommerce
    useEffect(() => {
        fetchBuilderConfig().then((cfg) => {
            if (cfg.presets) setPresets(cfg.presets);
            if (cfg.pricing) setPricingConfig(cfg.pricing);
        });

        fetchProducts().then((prods) => {
            setProducts(prods);
            let activeProd = null;
            if (initialProductId) {
                activeProd = prods.find((p) => p.id === parseInt(initialProductId));
            }
            if (!activeProd && prods.length > 0) {
                activeProd = prods[0];
            }
            if (activeProd) {
                setSelectedProduct(activeProd);
                if (activeProd.is_variable && activeProd.variations?.length > 0) {
                    let targetVar = null;
                    if (initialVariationId) {
                        targetVar = activeProd.variations.find((v) => v.id === parseInt(initialVariationId));
                    }
                    if (!targetVar) {
                        targetVar = findMatchingVariation(activeProd, initialWidth, initialHeight);
                    }
                    if (!targetVar) {
                        targetVar = activeProd.variations[0];
                    }

                    if (targetVar) {
                        setSelectedVariation(targetVar);
                        if (targetVar.parsed_width > 0 && targetVar.parsed_height > 0) {
                            handleSheetDimensionChange(targetVar.parsed_width, targetVar.parsed_height);
                        }
                        const p = calculatePricing(targetVar.parsed_width || sheetWidth, targetVar.parsed_height || sheetHeight, targetVar, activeProd, quantity);
                        setTotalPrice(p);
                    }
                } else {
                    const p = calculatePricing(sheetWidth, sheetHeight, null, activeProd, quantity);
                    setTotalPrice(p);
                }
            }
        });

        // Load existing sheet if token is present in URL or props (for Inspect in Builder / Customer reload)
        const urlParams = new URLSearchParams(window.location.search);
        const token = initialToken || urlParams.get('token') || '';
        if (token) {
            fetch(`${nexoraConfig.rest_url}sheets/${token}`)
                .then((r) => (r.ok ? r.json() : null))
                .then((data) => {
                    if (data && data.sheet) {
                        const s = data.sheet;
                        const w = parseFloat(s.width) || 22;
                        const h = parseFloat(s.height) || 60;
                        handleSheetDimensionChange(w, h);

                        if (s.canvas_data && fabricCanvas.fabricInstance.current) {
                            const parsed = typeof s.canvas_data === 'string' ? JSON.parse(s.canvas_data) : s.canvas_data;
                            fabricCanvas.fabricInstance.current.loadFromJSON(parsed, () => {
                                fabricCanvas.fabricInstance.current.renderAll();
                                fabricCanvas.updateLayersList();
                            });
                        }
                    }
                })
                .catch((err) => console.warn('Could not load sheet by token:', err));
        }
    }, [initialProductId, initialToken]);

    // Recalculate pricing on dimension, variation, product, or quantity changes
    useEffect(() => {
        const price = calculatePricing(sheetWidth, sheetHeight, selectedVariation, selectedProduct, quantity);
        setTotalPrice(price);
    }, [sheetWidth, sheetHeight, selectedVariation, selectedProduct, quantity, calculatePricing]);

    // Dimension Change Handler
    const handleSheetDimensionChange = (w, h) => {
        setSheets((prev) => {
            const updated = [...prev];
            updated[activeSheetIndex] = {
                ...updated[activeSheetIndex],
                width: w,
                height: h,
            };
            return updated;
        });

        if (selectedProduct && selectedProduct.is_variable) {
            const matched = findMatchingVariation(selectedProduct, w, h);
            if (matched && matched.id !== selectedVariation?.id) {
                setSelectedVariation(matched);
            }
        }
    };

    // Product & Variation selection handlers
    const handleSelectProduct = (product) => {
        setSelectedProduct(product);
        if (product && product.is_variable && product.variations?.length > 0) {
            const matchedVar = findMatchingVariation(product, sheetWidth, sheetHeight) || product.variations[0];
            setSelectedVariation(matchedVar);
            if (matchedVar && matchedVar.parsed_width > 0 && matchedVar.parsed_height > 0) {
                handleSheetDimensionChange(matchedVar.parsed_width, matchedVar.parsed_height);
            }
        } else {
            setSelectedVariation(null);
        }
    };

    const handleSelectVariation = (variation) => {
        setSelectedVariation(variation);
        if (variation && variation.parsed_width > 0 && variation.parsed_height > 0) {
            handleSheetDimensionChange(variation.parsed_width, variation.parsed_height);
        }
    };

    // Add Sheet
    const handleAddSheet = () => {
        const newIdx = sheets.length + 1;
        setSheets((prev) => [
            ...prev,
            { id: Date.now(), name: `Sheet ${newIdx}`, width: sheetWidth, height: sheetHeight },
        ]);
        setActiveSheetIndex(sheets.length);
    };

    // Save Design to user library
    const handleSaveDesign = async () => {
        if (!fabricCanvas.fabricInstance.current) return;
        const json = fabricCanvas.fabricInstance.current.toJSON();
        const preview = fabricCanvas.fabricInstance.current.toDataURL({ format: 'png', multiplier: 0.25 });

        const payload = {
            title: `Gang Sheet (${sheetWidth}"×${sheetHeight}")`,
            canvas_data: json,
            preview_base64: preview,
            width: sheetWidth,
            height: sheetHeight,
            unit,
            item_count: fabricCanvas.layers.length,
            utilization_rate: utilization,
            total_price: totalPrice,
            product_id: selectedProduct?.id || 0,
            variation_id: selectedVariation?.id || 0,
            variation: selectedVariation?.attributes || {},
        };

        const result = await saveGangSheet(payload);
        if (result && result.success) {
            alert('Gang Sheet saved successfully to your account!');
        }
    };

    // Add to Cart & Checkout
    const handleAddToCartAndCheckout = async () => {
        if (!fabricCanvas.fabricInstance.current) return;
        if (fabricCanvas.layers.length === 0) {
            alert('Your gang sheet is currently empty! Please place at least one design before checkout.');
            return;
        }

        setIsSubmitting(true);

        try {
            // Generate lightweight preview for cart thumbnail (max 300px, fast & reliable)
            const previewBase64 = fabricCanvas.fabricInstance.current.toDataURL({ format: 'png', multiplier: 0.2 });
            const json = fabricCanvas.fabricInstance.current.toJSON();

            const cartPayload = {
                product_id: selectedProduct?.id || (window.nexoraBuilderData ? window.nexoraBuilderData.default_product_id : 0) || 0,
                variation_id: selectedVariation?.id || 0,
                variation: selectedVariation?.attributes || {},
                quantity: Math.max(1, quantity),
                width: sheetWidth,
                height: sheetHeight,
                unit,
                title: selectedVariation
                    ? `${selectedProduct?.name || 'DTF Gang Sheet'} - ${selectedVariation.name || ''} (${sheetWidth}" × ${sheetHeight}")`
                    : (selectedProduct ? `${selectedProduct.name} (${sheetWidth}" × ${sheetHeight}")` : `Custom DTF Gang Sheet (${sheetWidth}" × ${sheetHeight}")`),
                canvas_data: json,
                preview_base64: previewBase64,
                item_count: fabricCanvas.layers.length,
                utilization_rate: utilization,
                total_price: totalPrice,
            };

            const response = await addToCartAndCheckout(cartPayload);

            // Confetti celebratory feedback!
            confetti({
                particleCount: 120,
                spread: 80,
                origin: { y: 0.6 },
            });

            // Target Cart URL
            const destinationUrl = (response && response.cart_url)
                ? response.cart_url
                : (nexoraConfig.cart_url || '/cart/');

            // Immediate smooth navigation to WooCommerce Cart page
            setTimeout(() => {
                window.location.href = destinationUrl;
            }, 600);
        } catch (e) {
            console.error('Checkout error:', e);
            const fallbackCart = nexoraConfig.cart_url || '/cart/';
            setTimeout(() => {
                window.location.href = fallbackCart;
            }, 600);
        }
    };

    return (
        <div className="nexora-app-container">
            {/* Top Navigation */}
            <TopNav
                sheets={sheets}
                activeSheetIndex={activeSheetIndex}
                onSelectSheet={setActiveSheetIndex}
                onAddSheet={handleAddSheet}
                onOpenMyDesigns={() => setIsMyDesignsModalOpen(true)}
                onOpenPreviewModal={() => setIsPreviewModalOpen(true)}
                cartCount={1}
                cartTotal={totalPrice}
                userName={nexoraConfig.user_name || 'Customer'}
            />

            {/* Main 3-Column Body */}
            <div className="nexora-main-body">
                {/* Left Sidebar */}
                <LeftSidebar
                    onAddImageToCanvas={fabricCanvas.addImageToCanvas}
                    uploadedMedia={uploadedMedia}
                    setUploadedMedia={setUploadedMedia}
                />

                {/* Center Workspace */}
                <div className="nexora-center-workspace">
                    <CanvasToolbar
                        zoom={fabricCanvas.zoom}
                        onZoomIn={() => fabricCanvas.setZoom((z) => Math.min(2.0, z + 0.1))}
                        onZoomOut={() => fabricCanvas.setZoom((z) => Math.max(0.2, z - 0.1))}
                        onZoomFit={() => fabricCanvas.setZoom(0.65)}
                        onZoomReset={() => fabricCanvas.setZoom(1)}
                        canUndo={fabricCanvas.canUndo}
                        canRedo={fabricCanvas.canRedo}
                        onUndo={fabricCanvas.undo}
                        onRedo={fabricCanvas.redo}
                        onClear={fabricCanvas.clearCanvas}
                        onDuplicate={fabricCanvas.duplicate}
                        onDelete={fabricCanvas.deleteSelected}
                        onFlipH={fabricCanvas.flipHorizontal}
                        onFlipV={fabricCanvas.flipVertical}
                        onRotate90={fabricCanvas.rotate90}
                        onAlign={fabricCanvas.alignObject}
                        selectedObject={fabricCanvas.selectedObject}
                        onToggleLock={fabricCanvas.toggleLock}
                    />

                    <CanvasWorkspace
                        canvasRef={fabricCanvas.canvasRef}
                        sheetWidth={sheetWidth}
                        sheetHeight={sheetHeight}
                        unit={unit}
                        margin={margin}
                        zoom={fabricCanvas.zoom}
                        canvasBg={canvasBg}
                        selectedObject={fabricCanvas.selectedObject}
                        PPI={fabricCanvas.PPI}
                    />
                </div>

                {/* Right Sidebar */}
                <RightSidebar
                    fabricInstance={fabricCanvas.fabricInstance}
                    layers={fabricCanvas.layers}
                    onToggleLock={fabricCanvas.toggleLock}
                    selectedObject={fabricCanvas.selectedObject}
                    sheetWidth={sheetWidth}
                    sheetHeight={sheetHeight}
                    onSheetDimensionChange={handleSheetDimensionChange}
                    presets={presets}
                    pricingConfig={pricingConfig}
                    unit={unit}
                    onUnitChange={setUnit}
                    margin={margin}
                    onMarginChange={setMargin}
                    canvasBg={canvasBg}
                    onCanvasBgChange={setCanvasBg}
                    products={products}
                    selectedProduct={selectedProduct}
                    onSelectProduct={handleSelectProduct}
                    selectedVariation={selectedVariation}
                    onSelectVariation={handleSelectVariation}
                    quantity={quantity}
                    onQuantityChange={setQuantity}
                    totalPrice={totalPrice}
                    utilization={utilization}
                    usedArea={usedArea}
                    wastedArea={wastedArea}
                    PPI={fabricCanvas.PPI}
                    onAutoArrangeApplied={(util, used, wasted) => {
                        setUtilization(util);
                        setUsedArea(used);
                        setWastedArea(wasted);
                    }}
                />
            </div>

            {/* Bottom Toolbar */}
            <BottomToolbar
                totalPrice={totalPrice}
                quantity={quantity}
                onQuantityChange={setQuantity}
                utilization={utilization}
                activeSheetIndex={activeSheetIndex}
                totalSheets={sheets.length}
                sheetWidth={sheetWidth}
                sheetHeight={sheetHeight}
                unit={unit}
                zoom={fabricCanvas.zoom}
                setZoom={fabricCanvas.setZoom}
                onSaveDesign={handleSaveDesign}
                onOpenExportModal={() => setIsExportModalOpen(true)}
                onAddToCartAndCheckout={handleAddToCartAndCheckout}
                isSubmitting={isSubmitting}
            />

            {/* Modals */}
            <ExportModal
                isOpen={isExportModalOpen}
                onClose={() => setIsExportModalOpen(false)}
                fabricInstance={fabricCanvas.fabricInstance}
                sheetWidth={sheetWidth}
                sheetHeight={sheetHeight}
            />

            <PreviewModal
                isOpen={isPreviewModalOpen}
                onClose={() => setIsPreviewModalOpen(false)}
                fabricInstance={fabricCanvas.fabricInstance}
                sheetWidth={sheetWidth}
                sheetHeight={sheetHeight}
            />

            <MyDesignsModal
                isOpen={isMyDesignsModalOpen}
                onClose={() => setIsMyDesignsModalOpen(false)}
                onLoadDesign={(design) => {
                    if (design.canvas_json && fabricCanvas.fabricInstance.current) {
                        fabricCanvas.fabricInstance.current.loadFromJSON(design.canvas_json, () => {
                            fabricCanvas.fabricInstance.current.renderAll();
                            fabricCanvas.updateLayersList();
                        });
                    }
                }}
            />
        </div>
    );
}

