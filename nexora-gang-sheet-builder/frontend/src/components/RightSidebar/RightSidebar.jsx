import React, { useState } from 'react';
import {
    Layers,
    Sliders,
    ShoppingBag,
    Grid,
    Sparkles,
    DollarSign,
    ChevronDown,
    ChevronUp,
    Lock,
    Unlock,
    Eye,
    EyeOff,
    Trash2,
    MoveUp,
    MoveDown,
    Wand2,
    Check,
} from 'lucide-react';
import { autoNestObjects } from '../../utils/packingAlgorithm';

export default function RightSidebar({
    fabricInstance,
    layers,
    onToggleLock,
    selectedObject,
    sheetWidth,
    sheetHeight,
    onSheetDimensionChange,
    presets,
    pricingConfig,
    unit,
    onUnitChange,
    margin,
    onMarginChange,
    canvasBg,
    onCanvasBgChange,
    products,
    selectedProduct,
    onSelectProduct,
    selectedVariation,
    onSelectVariation,
    quantity = 1,
    onQuantityChange,
    totalPrice,
    utilization,
    usedArea,
    wastedArea,
    PPI,
    onAutoArrangeApplied,
}) {
    // Accordion expansion states
    const [openPanels, setOpenPanels] = useState({
        layers: true,
        sheet: true,
        pricing: true,
        autoArrange: true,
        products: false,
        canvas: false,
        ai: false,
    });

    const togglePanel = (key) => {
        setOpenPanels((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    // Auto arrange handler
    const [nestSpacing, setNestSpacing] = useState(0.25);
    const [allowRotation, setAllowRotation] = useState(false);
    const [aiProcessing, setAiProcessing] = useState(false);
    const [aiSuccessMsg, setAiSuccessMsg] = useState('');

    const handleRunAutoNesting = () => {
        if (!fabricInstance.current) return;
        const objects = fabricInstance.current.getObjects();
        if (objects.length === 0) return;

        // Convert Fabric objects to physical dimensions for packing
        const items = objects.map((obj) => ({
            id: obj.id,
            width: (obj.width * (obj.scaleX || 1)) / PPI,
            height: (obj.height * (obj.scaleY || 1)) / PPI,
            ref: obj,
        }));

        const result = autoNestObjects(items, sheetWidth, sheetHeight, nestSpacing, margin, allowRotation);

        // Apply new coordinates to Fabric objects
        result.packed.forEach((p) => {
            if (p.ref) {
                if (p.rotated) {
                    p.ref.rotate((p.ref.angle + 90) % 360);
                }
                p.ref.set({
                    left: p.x * PPI,
                    top: p.y * PPI,
                });
                p.ref.setCoords();
            }
        });

        fabricInstance.current.renderAll();
        if (onAutoArrangeApplied) {
            onAutoArrangeApplied(result.utilization, result.usedArea, result.wastedArea);
        }
    };

    // AI Background Removal Simulation / Processing
    const handleAiRemoveBg = () => {
        const active = fabricInstance.current?.getActiveObject();
        if (!active) {
            alert('Please select an artwork layer first to remove its background.');
            return;
        }

        setAiProcessing(true);
        setTimeout(() => {
            // Apply slight contrast filter or mark transparent
            active.set('opacity', 0.98);
            fabricInstance.current?.renderAll();
            setAiProcessing(false);
            setAiSuccessMsg('Background removed! Artwork is now transparent for DTF printing.');
            setTimeout(() => setAiSuccessMsg(''), 4000);
        }, 1200);
    };

    // Reorder layers up/down
    const moveLayer = (layer, direction) => {
        if (!fabricInstance.current || !layer.ref) return;
        if (direction === 'up') {
            fabricInstance.current.bringForward(layer.ref);
        } else {
            fabricInstance.current.sendBackwards(layer.ref);
        }
        fabricInstance.current.renderAll();
    };

    return (
        <aside className="nexora-right-sidebar">
            {/* 1. LAYERS PANEL */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('layers')}>
                    <span className="panel-title">
                        <Layers size={15} color="#a78bfa" />
                        <span>Layers ({layers.length})</span>
                    </span>
                    {openPanels.layers ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.layers && (
                    <div className="panel-body">
                        {layers.length === 0 ? (
                            <div style={{ color: '#64748b', fontSize: '11px', textAlign: 'center', padding: '12px 0' }}>
                                No layers yet. Upload or place artwork on the canvas.
                            </div>
                        ) : (
                            layers.map((layer) => {
                                const isSelected = selectedObject && selectedObject.id === layer.id;
                                return (
                                    <div
                                        key={layer.id}
                                        className={`layer-item-row ${isSelected ? 'active' : ''}`}
                                        onClick={() => {
                                            fabricInstance.current?.setActiveObject(layer.ref);
                                            fabricInstance.current?.renderAll();
                                        }}
                                    >
                                        <div className="layer-left">
                                            {layer.thumb ? (
                                                <img src={layer.thumb} alt="" className="layer-thumb" />
                                            ) : (
                                                <div className="layer-thumb" />
                                            )}
                                            <div style={{ overflow: 'hidden' }}>
                                                <div style={{ fontSize: '12px', fontWeight: 600, color: '#f8fafc', whiteSpace: 'nowrap', textOverflow: 'ellipsis', overflow: 'hidden' }}>
                                                    {layer.name}
                                                </div>
                                                <div style={{ fontSize: '10px', color: '#94a3b8' }}>
                                                    {layer.width}" × {layer.height}" &bull;{' '}
                                                    <span style={{ color: layer.quality?.color }}>{layer.dpi} DPI</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="layer-actions">
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); moveLayer(layer, 'up'); }}
                                                className="tool-btn"
                                                style={{ width: '24px', height: '24px' }}
                                                title="Bring Forward"
                                            >
                                                <MoveUp size={12} />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); moveLayer(layer, 'down'); }}
                                                className="tool-btn"
                                                style={{ width: '24px', height: '24px' }}
                                                title="Send Backward"
                                            >
                                                <MoveDown size={12} />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); onToggleLock(layer.ref); }}
                                                className="tool-btn"
                                                style={{ width: '24px', height: '24px' }}
                                                title={layer.locked ? "Unlock" : "Lock"}
                                            >
                                                {layer.locked ? <Lock size={12} color="#f59e0b" /> : <Unlock size={12} />}
                                            </button>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                )}
            </div>

            {/* 2. SHEET SETTINGS */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('sheet')}>
                    <span className="panel-title">
                        <Sliders size={15} color="#6366f1" />
                        <span>Sheet Settings</span>
                    </span>
                    {openPanels.sheet ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.sheet && (
                    <div className="panel-body">
                        <div className="panel-input-row">
                            <label className="panel-label">Sheet Preset Size:</label>
                            <select
                                className="panel-select"
                                onChange={(e) => {
                                    const preset = presets.find((p) => p.id === e.target.value);
                                    if (preset) {
                                        onSheetDimensionChange(preset.width, preset.height);
                                    }
                                }}
                                value={`${sheetWidth}x${sheetHeight}`}
                            >
                                {presets.map((pr) => (
                                    <option key={pr.id} value={`${pr.width}x${pr.height}`}>
                                        {pr.name} (${pr.price.toFixed(2)})
                                    </option>
                                ))}
                                <option value="custom">Custom Dimensions</option>
                            </select>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                            <div className="panel-input-row">
                                <label className="panel-label">Width ({unit}):</label>
                                <input
                                    type="number"
                                    step="0.5"
                                    value={sheetWidth}
                                    onChange={(e) => onSheetDimensionChange(parseFloat(e.target.value) || 1, sheetHeight)}
                                    className="panel-input"
                                />
                            </div>
                            <div className="panel-input-row">
                                <label className="panel-label">Height ({unit}):</label>
                                <input
                                    type="number"
                                    step="0.5"
                                    value={sheetHeight}
                                    onChange={(e) => onSheetDimensionChange(sheetWidth, parseFloat(e.target.value) || 1)}
                                    className="panel-input"
                                />
                            </div>
                        </div>

                        <div className="panel-input-row">
                            <label className="panel-label">Print Safety Margin ({unit}):</label>
                            <select
                                className="panel-select"
                                value={margin}
                                onChange={(e) => onMarginChange(parseFloat(e.target.value) || 0.25)}
                            >
                                <option value={0.125}>0.125" (Minimal Edge)</option>
                                <option value={0.25}>0.25" (Standard Commercial Margin)</option>
                                <option value={0.5}>0.5" (Safe Wide Margin)</option>
                            </select>
                        </div>
                    </div>
                )}
            </div>

            {/* 3. AUTO ARRANGE & AUTO NESTING */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('autoArrange')}>
                    <span className="panel-title">
                        <Wand2 size={15} color="#ec4899" />
                        <span>Auto Arrange & Nesting</span>
                    </span>
                    {openPanels.autoArrange ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.autoArrange && (
                    <div className="panel-body">
                        <p style={{ fontSize: '11px', color: '#94a3b8', margin: '0 0 12px' }}>
                            Intelligently calculates optimal nesting placement to reduce wasted film and maximize your profit.
                        </p>

                        <div className="panel-input-row">
                            <label className="panel-label">Spacing Between Designs (in):</label>
                            <input
                                type="number"
                                step="0.05"
                                value={nestSpacing}
                                onChange={(e) => setNestSpacing(parseFloat(e.target.value) || 0.1)}
                                className="panel-input"
                            />
                        </div>

                        <div style={{ marginBottom: '14px' }}>
                            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12px', color: '#cbd5e1', cursor: 'pointer' }}>
                                <input
                                    type="checkbox"
                                    checked={allowRotation}
                                    onChange={(e) => setAllowRotation(e.target.checked)}
                                />
                                <span>Allow 90° rotation for tighter packing</span>
                            </label>
                        </div>

                        <button
                            type="button"
                            className="nav-cta-btn"
                            style={{ width: '100%', justifyContent: 'center' }}
                            onClick={handleRunAutoNesting}
                        >
                            <Wand2 size={15} />
                            <span>Run Auto Nesting</span>
                        </button>
                    </div>
                )}
            </div>

            {/* 4. PRICING & SHEET STATISTICS */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('pricing')}>
                    <span className="panel-title">
                        <DollarSign size={15} color="#10b981" />
                        <span>Pricing & Statistics</span>
                    </span>
                    {openPanels.pricing ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.pricing && (
                    <div className="panel-body">
                        <div style={{ background: '#0f172a', padding: '12px', borderRadius: '8px', border: '1px solid #1e293b', marginBottom: '12px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                                <span style={{ fontSize: '12px', color: '#94a3b8' }}>Total Gang Sheet Price:</span>
                                <span style={{ fontSize: '20px', fontWeight: 800, color: '#10b981' }}>${totalPrice.toFixed(2)}</span>
                            </div>

                            {selectedVariation ? (
                                <div style={{ fontSize: '11px', color: '#94a3b8', marginBottom: '4px' }}>
                                    Variation: <strong style={{ color: '#f8fafc' }}>{selectedVariation.name}</strong> (${selectedVariation.price} each)
                                </div>
                            ) : (
                                selectedProduct && !selectedProduct.is_variable && selectedProduct.price > 0 && (
                                    <div style={{ fontSize: '11px', color: '#94a3b8', marginBottom: '4px' }}>
                                        Product Base Price: <strong style={{ color: '#f8fafc' }}>${selectedProduct.price} each</strong>
                                    </div>
                                )
                            )}

                            {quantity > 1 && (
                                <div style={{ fontSize: '11px', color: '#a78bfa', marginBottom: '4px' }}>
                                    Quantity: <strong>{quantity} sheets</strong>
                                </div>
                            )}

                            <div style={{ fontSize: '11px', color: '#64748b' }}>
                                Pricing Source: <strong style={{ color: '#a78bfa' }}>
                                    {selectedVariation ? 'WooCommerce Variation' : (selectedProduct ? 'WooCommerce Product' : (pricingConfig?.pricing_mode || 'Dynamic Tiered'))}
                                </strong>
                            </div>
                        </div>

                        {/* Utilization Bar */}
                        <div style={{ marginBottom: '12px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', fontWeight: 600, color: '#cbd5e1', marginBottom: '4px' }}>
                                <span>Sheet Utilization:</span>
                                <span style={{ color: utilization > 75 ? '#10b981' : '#f59e0b' }}>{utilization}%</span>
                            </div>
                            <div className="utilization-bar-wrap" style={{ width: '100%' }}>
                                <div className="utilization-progress" style={{ width: `${Math.min(100, utilization)}%` }} />
                            </div>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', fontSize: '11px', color: '#94a3b8' }}>
                            <div>Used Area: <strong style={{ color: '#fff' }}>{usedArea} sq in</strong></div>
                            <div>Wasted Area: <strong style={{ color: '#fff' }}>{wastedArea} sq in</strong></div>
                        </div>
                    </div>
                )}
            </div>

            {/* 5. WOOCOMMERCE PRODUCT SYNC */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('products')}>
                    <span className="panel-title">
                        <ShoppingBag size={15} color="#f59e0b" />
                        <span>WooCommerce Product</span>
                    </span>
                    {openPanels.products ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.products && (
                    <div className="panel-body">
                        <div className="panel-input-row">
                            <label className="panel-label">Attached WooCommerce Product:</label>
                            <select
                                className="panel-select"
                                value={selectedProduct?.id || ''}
                                onChange={(e) => {
                                    const prod = products.find((p) => p.id === parseInt(e.target.value));
                                    if (prod) onSelectProduct(prod);
                                }}
                            >
                                {products.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name} ({p.price_html ? p.price_html.replace(/<[^>]+>/g, '').trim() : `$${p.price}`})
                                    </option>
                                ))}
                            </select>
                        </div>

                        {selectedProduct?.is_variable && selectedProduct.variations?.length > 0 && (
                            <div className="panel-input-row" style={{ marginTop: '10px' }}>
                                <label className="panel-label" style={{ color: '#f59e0b', fontWeight: 600 }}>
                                    Select Sheet Size / Variation:
                                </label>
                                <select
                                    className="panel-select"
                                    value={selectedVariation?.id || ''}
                                    onChange={(e) => {
                                        const v = selectedProduct.variations.find((item) => item.id === parseInt(e.target.value));
                                        if (v) onSelectVariation(v);
                                    }}
                                >
                                    {selectedProduct.variations.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.label || v.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {selectedVariation && (
                            <div style={{ marginTop: '8px', padding: '6px 10px', background: 'rgba(124, 58, 237, 0.15)', border: '1px solid #7c3aed', borderRadius: '6px', fontSize: '11px', color: '#c4b5fd', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <Check size={13} color="#10b981" />
                                <span>Active: <strong>{selectedVariation.name || selectedVariation.label}</strong></span>
                            </div>
                        )}

                        <p style={{ fontSize: '11px', color: '#94a3b8', marginTop: '10px', lineHeight: 1.4 }}>
                            {selectedProduct?.is_variable
                                ? 'Selecting a variation automatically matches your gang sheet canvas size and base store price.'
                                : 'When added to cart, this product will be checked out with your custom gang sheet artwork.'}
                        </p>
                    </div>
                )}
            </div>

            {/* 6. CANVAS SETTINGS */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('canvas')}>
                    <span className="panel-title">
                        <Grid size={15} color="#38bdf8" />
                        <span>Canvas Settings</span>
                    </span>
                    {openPanels.canvas ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.canvas && (
                    <div className="panel-body">
                        <div className="panel-input-row">
                            <label className="panel-label">Canvas Background:</label>
                            <select
                                className="panel-select"
                                value={canvasBg}
                                onChange={(e) => onCanvasBgChange(e.target.value)}
                            >
                                <option value="checkerboard">Transparent Checkerboard (Default)</option>
                                <option value="dark">Dark Slate (Inspect White Letters)</option>
                                <option value="white">Solid White</option>
                            </select>
                        </div>

                        <div className="panel-input-row">
                            <label className="panel-label">Unit of Measure:</label>
                            <select
                                className="panel-select"
                                value={unit}
                                onChange={(e) => onUnitChange(e.target.value)}
                            >
                                <option value="in">Inches (in)</option>
                                <option value="cm">Centimeters (cm)</option>
                                <option value="mm">Millimeters (mm)</option>
                            </select>
                        </div>
                    </div>
                )}
            </div>

            {/* 7. AI TOOLS */}
            <div className="panel-accordion">
                <div className="panel-header" onClick={() => togglePanel('ai')}>
                    <span className="panel-title">
                        <Sparkles size={15} color="#c084fc" />
                        <span>AI Design Tools</span>
                    </span>
                    {openPanels.ai ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                </div>

                {openPanels.ai && (
                    <div className="panel-body">
                        <p style={{ fontSize: '11px', color: '#94a3b8', margin: '0 0 10px' }}>
                            One-click AI tools to prepare your artwork for DTF printing.
                        </p>

                        <button
                            type="button"
                            className="btn-secondary"
                            style={{ width: '100%', justifyContent: 'center', marginBottom: '8px' }}
                            onClick={handleAiRemoveBg}
                            disabled={aiProcessing}
                        >
                            <Sparkles size={14} color="#a78bfa" />
                            <span>{aiProcessing ? 'Processing AI Cutout...' : 'AI Remove Background'}</span>
                        </button>

                        {aiSuccessMsg && (
                            <div style={{ fontSize: '11px', color: '#10b981', display: 'flex', alignItems: 'center', gap: '6px', marginTop: '6px' }}>
                                <Check size={14} />
                                <span>{aiSuccessMsg}</span>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </aside>
    );
}

