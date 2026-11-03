import React, { useState } from 'react';
import { X, Sparkles, Shirt } from 'lucide-react';

export default function PreviewModal({
    isOpen,
    onClose,
    fabricInstance,
    sheetWidth,
    sheetHeight,
}) {
    const [previewMode, setPreviewMode] = useState('sheet'); // 'sheet', 'black_shirt', 'white_shirt'

    if (!isOpen) return null;

    const dataUrl = fabricInstance.current
        ? fabricInstance.current.toDataURL({ format: 'png', multiplier: 0.5 })
        : '';

    return (
        <div className="nexora-modal-backdrop">
            <div className="nexora-modal-content" style={{ maxWidth: '750px', maxHeight: '90vh' }}>
                <div className="modal-header">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <Sparkles size={18} color="#a78bfa" />
                        <h3 style={{ margin: 0, fontSize: '15px', color: '#fff' }}>Real-Time Preview & DTF Mockup</h3>
                    </div>
                    <button type="button" onClick={onClose} style={{ background: 'transparent', border: 'none', color: '#94a3b8', cursor: 'pointer' }}>
                        <X size={18} />
                    </button>
                </div>

                <div className="modal-body" style={{ textAlign: 'center' }}>
                    {/* Mode toggles */}
                    <div style={{ display: 'inline-flex', gap: '6px', background: '#0f172a', padding: '4px', borderRadius: '8px', marginBottom: '16px', border: '1px solid #1e293b' }}>
                        <button
                            type="button"
                            className={`cloud-btn ${previewMode === 'sheet' ? 'active' : ''}`}
                            onClick={() => setPreviewMode('sheet')}
                            style={{ background: previewMode === 'sheet' ? '#7c3aed' : 'transparent', border: 'none' }}
                        >
                            Full Gang Sheet View
                        </button>
                        <button
                            type="button"
                            className={`cloud-btn ${previewMode === 'black_shirt' ? 'active' : ''}`}
                            onClick={() => setPreviewMode('black_shirt')}
                            style={{ background: previewMode === 'black_shirt' ? '#7c3aed' : 'transparent', border: 'none' }}
                        >
                            <Shirt size={13} />
                            <span>Black Shirt Mockup</span>
                        </button>
                        <button
                            type="button"
                            className={`cloud-btn ${previewMode === 'white_shirt' ? 'active' : ''}`}
                            onClick={() => setPreviewMode('white_shirt')}
                            style={{ background: previewMode === 'white_shirt' ? '#7c3aed' : 'transparent', border: 'none' }}
                        >
                            <Shirt size={13} />
                            <span>White Shirt Mockup</span>
                        </button>
                    </div>

                    {/* Preview Viewport */}
                    <div
                        style={{
                            minHeight: '360px',
                            maxHeight: '520px',
                            overflow: 'auto',
                            background: previewMode === 'black_shirt' ? '#111827' : previewMode === 'white_shirt' ? '#f3f4f6' : '#080a0f',
                            borderRadius: '10px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '24px',
                            border: '1px solid #263143',
                            position: 'relative',
                        }}
                    >
                        {previewMode === 'sheet' ? (
                            <div className="checkerboard-bg" style={{ padding: '8px', borderRadius: '6px', boxShadow: '0 8px 30px rgba(0,0,0,0.8)' }}>
                                <img src={dataUrl} alt="Gang Sheet" style={{ maxHeight: '440px', maxWidth: '100%', objectFit: 'contain' }} />
                            </div>
                        ) : (
                            <div style={{ position: 'relative', width: '380px', height: '420px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                {/* T-shirt vector silhouette */}
                                <svg width="340" height="380" viewBox="0 0 100 100" fill={previewMode === 'black_shirt' ? '#1f2937' : '#ffffff'} stroke={previewMode === 'black_shirt' ? '#374151' : '#cbd5e1'} strokeWidth="1" style={{ filter: 'drop-shadow(0 10px 20px rgba(0,0,0,0.4))' }}>
                                    <path d="M30,10 L38,18 C45,22 55,22 62,18 L70,10 L92,24 L82,42 L72,34 L72,88 C72,90 70,92 68,92 L32,92 C30,92 28,90 28,88 L28,34 L18,42 L8,24 Z" />
                                </svg>
                                {/* Artwork placed on chest */}
                                <div style={{ position: 'absolute', top: '90px', width: '130px', maxHeight: '180px', overflow: 'hidden', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                    <img src={dataUrl} alt="Chest Print" style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain' }} />
                                </div>
                            </div>
                        )}
                    </div>

                    <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '12px' }}>
                        Physical Size: {sheetWidth}" × {sheetHeight}" &bull; Commercial DTF Direct-to-Film transfer preview
                    </div>
                </div>

                <div className="modal-footer">
                    <button type="button" className="btn-secondary" onClick={onClose}>
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}

