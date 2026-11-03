import React, { useState, useRef } from 'react';
import { UploadCloud, Image as ImageIcon, Cloud, Zap, Search, Trash2, Plus, AlertTriangle } from 'lucide-react';
import CloudImportModal from './CloudImportModal';
import { uploadArtworkFile } from '../../utils/api';
import { calculateDpi, getDpiQuality } from '../../utils/dpiChecker';

export default function LeftSidebar({
    onAddImageToCanvas,
    uploadedMedia,
    setUploadedMedia,
}) {
    const [activeTab, setActiveTab] = useState('upload'); // 'upload', 'library', 'cloud', 'quick'
    const [searchQuery, setSearchQuery] = useState('');
    const [isDragging, setIsDragging] = useState(false);
    const [uploadQuantity, setUploadQuantity] = useState(1);
    const [isCloudModalOpen, setIsCloudModalOpen] = useState(false);
    const fileInputRef = useRef(null);

    // Quick Order input state
    const [quickWidth, setQuickWidth] = useState(10);
    const [quickHeight, setQuickHeight] = useState(10);
    const [quickQty, setQuickQty] = useState(3);

    // Process file uploads
    const handleFiles = async (files) => {
        if (!files || files.length === 0) return;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            try {
                const mediaItem = await uploadArtworkFile(file);
                
                // Add to library state
                setUploadedMedia((prev) => [mediaItem, ...prev]);

                // Place onto canvas with quantity
                for (let q = 0; q < uploadQuantity; q++) {
                    onAddImageToCanvas(mediaItem.url, mediaItem.width, mediaItem.height, 10);
                }
            } catch (err) {
                console.error('File upload error:', err);
            }
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files) {
            handleFiles(e.dataTransfer.files);
        }
    };

    const handleFileChange = (e) => {
        if (e.target.files) {
            handleFiles(e.target.files);
        }
    };

    // Filter library items by search
    const filteredLibrary = uploadedMedia.filter((item) =>
        (item.name || '').toLowerCase().includes(searchQuery.toLowerCase())
    );

    const handleDeleteMedia = (id, e) => {
        e.stopPropagation();
        setUploadedMedia((prev) => prev.filter((item) => item.id !== id));
    };

    const handleQuickOrderAdd = () => {
        // Find first item in media or default sample
        const sampleUrl = uploadedMedia.length > 0 ? uploadedMedia[0].url : 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80';
        for (let i = 0; i < quickQty; i++) {
            onAddImageToCanvas(sampleUrl, 1200, 1200, quickWidth);
        }
    };

    return (
        <aside className="nexora-left-sidebar">
            {/* Top Navigation Tabs for Left Sidebar */}
            <div className="left-sidebar-nav">
                <button
                    type="button"
                    className={`left-nav-item ${activeTab === 'upload' ? 'active' : ''}`}
                    onClick={() => setActiveTab('upload')}
                >
                    <UploadCloud size={16} />
                    <span>Upload</span>
                </button>
                <button
                    type="button"
                    className={`left-nav-item ${activeTab === 'library' ? 'active' : ''}`}
                    onClick={() => setActiveTab('library')}
                >
                    <ImageIcon size={16} />
                    <span>Library ({uploadedMedia.length})</span>
                </button>
                <button
                    type="button"
                    className={`left-nav-item ${activeTab === 'quick' ? 'active' : ''}`}
                    onClick={() => setActiveTab('quick')}
                >
                    <Zap size={16} />
                    <span>Quick Order</span>
                </button>
            </div>

            {/* Content Area */}
            <div className="left-content-area">
                {/* 1. UPLOAD TAB */}
                {activeTab === 'upload' && (
                    <div>
                        <div
                            className={`upload-dropzone ${isDragging ? 'dragover' : ''}`}
                            onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                            onDragLeave={() => setIsDragging(false)}
                            onDrop={handleDrop}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                multiple
                                accept=".png,.jpg,.jpeg,.webp,.svg,.tif,.tiff,.pdf"
                                style={{ display: 'none' }}
                                onChange={handleFileChange}
                            />
                            <UploadCloud className="dropzone-icon" />
                            <div style={{ fontWeight: 700, fontSize: '13px', color: '#fff', marginBottom: '4px' }}>
                                Drag & Drop Designs Here
                            </div>
                            <div style={{ fontSize: '11px', color: '#94a3b8', marginBottom: '10px' }}>
                                or browse files on your computer
                            </div>
                            <div style={{ fontSize: '10px', color: '#64748b', background: '#0f172a', padding: '4px 8px', borderRadius: '4px', display: 'inline-block' }}>
                                PNG, JPG, WEBP, SVG, TIFF, PDF (Up to 150MB)
                            </div>
                        </div>

                        {/* Quantity to place */}
                        <div style={{ marginTop: '16px', background: 'var(--nx-bg-card)', padding: '12px', borderRadius: '8px', border: '1px solid var(--nx-border)' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ fontSize: '11px', fontWeight: 600, color: '#94a3b8' }}>Place Copies on Upload:</span>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                    {[1, 2, 5, 10].map((qty) => (
                                        <button
                                            key={qty}
                                            type="button"
                                            onClick={() => setUploadQuantity(qty)}
                                            style={{
                                                background: uploadQuantity === qty ? '#7c3aed' : '#0f172a',
                                                border: '1px solid #334155',
                                                color: '#fff',
                                                fontSize: '11px',
                                                padding: '2px 8px',
                                                borderRadius: '4px',
                                                cursor: 'pointer'
                                            }}
                                        >
                                            {qty}x
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Cloud Import Section */}
                        <div style={{ marginTop: '20px' }}>
                            <div style={{ fontSize: '11px', fontWeight: 700, textTransform: 'uppercase', color: '#64748b', marginBottom: '8px' }}>
                                Cloud Import
                            </div>
                            <div className="cloud-import-grid">
                                <button
                                    type="button"
                                    className="cloud-btn"
                                    onClick={() => setIsCloudModalOpen(true)}
                                >
                                    <Cloud size={14} color="#3b82f6" />
                                    <span>Google Drive</span>
                                </button>
                                <button
                                    type="button"
                                    className="cloud-btn"
                                    onClick={() => setIsCloudModalOpen(true)}
                                >
                                    <Cloud size={14} color="#0284c7" />
                                    <span>Dropbox</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* 2. LIBRARY TAB */}
                {activeTab === 'library' && (
                    <div>
                        <div className="library-search-box">
                            <Search size={14} className="library-search-icon" />
                            <input
                                type="text"
                                placeholder="Search uploaded artwork..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="library-search-input"
                            />
                        </div>

                        {filteredLibrary.length === 0 ? (
                            <div style={{ textAlign: 'center', padding: '30px 10px', color: '#64748b', fontSize: '12px' }}>
                                <ImageIcon size={32} style={{ margin: '0 auto 8px', opacity: 0.4 }} />
                                No designs found. Upload your first artwork!
                            </div>
                        ) : (
                            <div className="library-grid">
                                {filteredLibrary.map((item) => {
                                    const dpi = calculateDpi(item.width, item.height, 10, 10);
                                    const quality = getDpiQuality(dpi);
                                    return (
                                        <div
                                            key={item.id}
                                            className="library-card"
                                            onClick={() => onAddImageToCanvas(item.url, item.width, item.height, 10)}
                                            title="Click to place on gang sheet"
                                        >
                                            <div className="library-thumb-wrap">
                                                <img src={item.url} alt={item.name} className="library-thumb-img" />
                                            </div>
                                            <div className="library-item-title">{item.name || 'Artwork'}</div>
                                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                <span
                                                    className="library-dpi-tag"
                                                    style={{ background: quality.bgColor, color: quality.color }}
                                                >
                                                    {quality.label}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={(e) => handleDeleteMedia(item.id, e)}
                                                    style={{ background: 'transparent', border: 'none', color: '#64748b', cursor: 'pointer', padding: '2px' }}
                                                    title="Delete artwork"
                                                >
                                                    <Trash2 size={12} />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}

                {/* 3. QUICK ORDER TAB */}
                {activeTab === 'quick' && (
                    <div style={{ background: 'var(--nx-bg-card)', padding: '16px', borderRadius: '10px', border: '1px solid var(--nx-border)' }}>
                        <div style={{ fontWeight: 700, fontSize: '13px', color: '#fff', marginBottom: '4px' }}>
                            Fast Batch Placement
                        </div>
                        <p style={{ fontSize: '11px', color: '#94a3b8', margin: '0 0 14px' }}>
                            Quickly drop multiple sized copies of your artwork to fill up gang sheets efficiently.
                        </p>

                        <div className="panel-input-row">
                            <label className="panel-label">Design Width (Inches):</label>
                            <input
                                type="number"
                                step="0.5"
                                value={quickWidth}
                                onChange={(e) => setQuickWidth(parseFloat(e.target.value) || 1)}
                                className="panel-input"
                            />
                        </div>

                        <div className="panel-input-row">
                            <label className="panel-label">Design Height (Inches):</label>
                            <input
                                type="number"
                                step="0.5"
                                value={quickHeight}
                                onChange={(e) => setQuickHeight(parseFloat(e.target.value) || 1)}
                                className="panel-input"
                            />
                        </div>

                        <div className="panel-input-row">
                            <label className="panel-label">Quantity Needed:</label>
                            <input
                                type="number"
                                min="1"
                                max="100"
                                value={quickQty}
                                onChange={(e) => setQuickQty(parseInt(e.target.value) || 1)}
                                className="panel-input"
                            />
                        </div>

                        <button
                            type="button"
                            className="nav-cta-btn"
                            style={{ width: '100%', justifyContent: 'center', marginTop: '12px' }}
                            onClick={handleQuickOrderAdd}
                        >
                            <Plus size={16} />
                            <span>Add {quickQty} Copies to Sheet</span>
                        </button>
                    </div>
                )}
            </div>

            {/* Cloud Import Dialog */}
            <CloudImportModal
                isOpen={isCloudModalOpen}
                onClose={() => setIsCloudModalOpen(false)}
                onImportUrl={(url, name) => {
                    const newItem = { id: Date.now(), name, url, width: 1200, height: 1200, dpi: 300 };
                    setUploadedMedia((prev) => [newItem, ...prev]);
                    onAddImageToCanvas(url, 1200, 1200, 10);
                }}
            />
        </aside>
    );
}

