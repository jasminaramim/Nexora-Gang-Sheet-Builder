import React from 'react';
import { Layers, ShoppingCart, FolderHeart, Sparkles, User, Plus } from 'lucide-react';

export default function TopNav({
    sheets,
    activeSheetIndex,
    onSelectSheet,
    onAddSheet,
    onOpenMyDesigns,
    onOpenPreviewModal,
    cartCount = 0,
    cartTotal = 0,
    userName = 'Guest',
}) {
    return (
        <header className="nexora-topnav">
            {/* Left: Brand & Sheet Tabs */}
            <div className="topnav-left">
                <div className="brand-badge">
                    <div className="brand-icon">
                        <Layers size={18} color="#fff" />
                    </div>
                    <span className="brand-title">Nexora Gang Sheet</span>
                    <span className="brand-tag">DTF Pro</span>
                </div>

                {/* Multiple Sheets Support Tabs */}
                <div className="sheet-selector-tabs">
                    {sheets.map((sheet, idx) => (
                        <button
                            key={sheet.id || idx}
                            type="button"
                            className={`sheet-tab-btn ${idx === activeSheetIndex ? 'active' : ''}`}
                            onClick={() => onSelectSheet(idx)}
                        >
                            <span>Sheet {idx + 1}</span>
                            <small style={{ opacity: 0.7, fontSize: '10px' }}>
                                ({sheet.width}"×{sheet.height}")
                            </small>
                        </button>
                    ))}
                    <button
                        type="button"
                        className="sheet-add-btn"
                        onClick={onAddSheet}
                        title="Add Another Gang Sheet"
                    >
                        <Plus size={14} />
                    </button>
                </div>
            </div>

            {/* Right: Actions */}
            <div className="topnav-right">
                <button
                    type="button"
                    className="nav-icon-btn"
                    onClick={onOpenPreviewModal}
                    title="Real-Time T-Shirt Mockup & Quality Preview"
                >
                    <Sparkles size={15} color="#a78bfa" />
                    <span>3D Mockup Preview</span>
                </button>

                <button
                    type="button"
                    className="nav-icon-btn"
                    onClick={onOpenMyDesigns}
                    title="Open Saved Designs"
                >
                    <FolderHeart size={15} color="#ec4899" />
                    <span>My Designs</span>
                </button>

                <div className="nav-icon-btn" style={{ cursor: 'default' }}>
                    <ShoppingCart size={15} color="#10b981" />
                    <span>Cart: {cartCount} items (${cartTotal.toFixed(2)})</span>
                </div>

                <div className="nav-icon-btn" style={{ cursor: 'default', color: '#94a3b8' }}>
                    <User size={15} />
                    <span>{userName}</span>
                </div>
            </div>
        </header>
    );
}

