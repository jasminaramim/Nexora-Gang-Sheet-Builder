import React from 'react';
import { ShoppingCart, Download, Bookmark, ZoomIn, ZoomOut } from 'lucide-react';

export default function BottomToolbar({
    totalPrice,
    utilization,
    activeSheetIndex,
    totalSheets,
    sheetWidth,
    sheetHeight,
    unit = 'in',
    quantity = 1,
    onQuantityChange,
    zoom,
    setZoom,
    onSaveDesign,
    onOpenExportModal,
    onAddToCartAndCheckout,
    isSubmitting = false,
}) {
    return (
        <footer className="nexora-bottom-bar">
            {/* Left: Price, Quantity & Sheet Utilization */}
            <div className="bottom-stats-group">
                <div className="bottom-stat-item">
                    <span className="stat-title">Total Price</span>
                    <span className="stat-num-price">${totalPrice.toFixed(2)}</span>
                </div>

                <div className="bottom-stat-item" style={{ borderLeft: '1px solid var(--nx-border)', paddingLeft: '12px' }}>
                    <span className="stat-title">Sheet Qty</span>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
                        <button
                            type="button"
                            onClick={() => onQuantityChange && onQuantityChange(Math.max(1, quantity - 1))}
                            style={{ background: '#1e293b', border: '1px solid #334155', color: '#fff', borderRadius: '4px', width: '22px', height: '22px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '13px' }}
                            title="Decrease Quantity"
                        >-</button>
                        <span style={{ fontSize: '13px', fontWeight: 700, color: '#f8fafc', minWidth: '22px', textAlign: 'center' }}>{quantity}</span>
                        <button
                            type="button"
                            onClick={() => onQuantityChange && onQuantityChange(quantity + 1)}
                            style={{ background: '#1e293b', border: '1px solid #334155', color: '#fff', borderRadius: '4px', width: '22px', height: '22px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '13px' }}
                            title="Increase Quantity"
                        >+</button>
                    </div>
                </div>

                <div className="bottom-stat-item">
                    <span className="stat-title">Sheet Utilization: {utilization}%</span>
                    <div className="utilization-bar-wrap">
                        <div
                            className="utilization-progress"
                            style={{
                                width: `${Math.min(100, utilization)}%`,
                                background: utilization > 80 ? '#10b981' : '#7c3aed',
                            }}
                        />
                    </div>
                </div>

                <div className="bottom-stat-item" style={{ borderLeft: '1px solid var(--nx-border)', paddingLeft: '16px' }}>
                    <span className="stat-title">Active Sheet</span>
                    <span style={{ fontSize: '13px', fontWeight: 600, color: '#f8fafc' }}>
                        Sheet {activeSheetIndex + 1} of {totalSheets} ({sheetWidth}{unit} × {sheetHeight}{unit})
                    </span>
                </div>
            </div>

            {/* Center: Zoom slider */}
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <ZoomOut size={14} color="#94a3b8" />
                <input
                    type="range"
                    min="0.2"
                    max="2.0"
                    step="0.05"
                    value={zoom}
                    onChange={(e) => setZoom(parseFloat(e.target.value))}
                    style={{ width: '90px', accentColor: '#7c3aed', cursor: 'pointer' }}
                />
                <ZoomIn size={14} color="#94a3b8" />
                <span style={{ fontSize: '11px', color: '#94a3b8', width: '32px' }}>
                    {Math.round(zoom * 100)}%
                </span>
            </div>

            {/* Right: Actions */}
            <div className="bottom-actions-group">
                <button
                    type="button"
                    className="btn-secondary"
                    onClick={onSaveDesign}
                    title="Save current canvas to your account"
                >
                    <Bookmark size={15} color="#a78bfa" />
                    <span>Save Draft</span>
                </button>

                <button
                    type="button"
                    className="btn-secondary"
                    onClick={onOpenExportModal}
                    title="Export 300 DPI Print File (PNG/PDF)"
                >
                    <Download size={15} color="#38bdf8" />
                    <span>Export Print File</span>
                </button>

                <button
                    type="button"
                    className="btn-checkout"
                    onClick={onAddToCartAndCheckout}
                    disabled={isSubmitting}
                >
                    <ShoppingCart size={16} />
                    <span>{isSubmitting ? 'Processing Order...' : 'Add to Cart & Checkout'}</span>
                </button>
            </div>
        </footer>
    );
}

