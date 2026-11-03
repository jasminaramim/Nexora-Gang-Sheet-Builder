import React, { useState } from 'react';
import { Cloud, X, Link, Check, ExternalLink } from 'lucide-react';

export default function CloudImportModal({ isOpen, onClose, onImportUrl }) {
    const [cloudType, setCloudType] = useState('drive'); // 'drive', 'dropbox', 'url'
    const [urlInput, setUrlInput] = useState('');
    const [importing, setImporting] = useState(false);

    if (!isOpen) return null;

    const handleImportSubmit = (e) => {
        e.preventDefault();
        if (!urlInput.trim()) return;

        setImporting(true);
        setTimeout(() => {
            onImportUrl(urlInput.trim(), `${cloudType}-artwork-${Date.now()}.png`);
            setImporting(false);
            setUrlInput('');
            onClose();
        }, 600);
    };

    return (
        <div className="nexora-modal-backdrop">
            <div className="nexora-modal-content" style={{ maxWidth: '480px' }}>
                <div className="modal-header">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <Cloud size={18} color="#a78bfa" />
                        <h3 style={{ margin: 0, fontSize: '15px', color: '#fff' }}>Cloud Artwork Import</h3>
                    </div>
                    <button type="button" onClick={onClose} style={{ background: 'transparent', border: 'none', color: '#94a3b8', cursor: 'pointer' }}>
                        <X size={18} />
                    </button>
                </div>

                <div className="modal-body">
                    <div style={{ display: 'flex', gap: '8px', marginBottom: '16px' }}>
                        <button
                            type="button"
                            className={`cloud-btn ${cloudType === 'drive' ? 'active' : ''}`}
                            onClick={() => setCloudType('drive')}
                            style={{ flex: 1, borderColor: cloudType === 'drive' ? '#7c3aed' : '' }}
                        >
                            Google Drive
                        </button>
                        <button
                            type="button"
                            className={`cloud-btn ${cloudType === 'dropbox' ? 'active' : ''}`}
                            onClick={() => setCloudType('dropbox')}
                            style={{ flex: 1, borderColor: cloudType === 'dropbox' ? '#7c3aed' : '' }}
                        >
                            Dropbox
                        </button>
                        <button
                            type="button"
                            className={`cloud-btn ${cloudType === 'url' ? 'active' : ''}`}
                            onClick={() => setCloudType('url')}
                            style={{ flex: 1, borderColor: cloudType === 'url' ? '#7c3aed' : '' }}
                        >
                            Direct Image URL
                        </button>
                    </div>

                    <form onSubmit={handleImportSubmit}>
                        <div style={{ marginBottom: '12px' }}>
                            <label style={{ display: 'block', fontSize: '11px', color: '#94a3b8', marginBottom: '6px' }}>
                                {cloudType === 'drive' && 'Paste Google Drive Shareable File Link:'}
                                {cloudType === 'dropbox' && 'Paste Dropbox Shared Artwork Link:'}
                                {cloudType === 'url' && 'Enter Direct Image Web Address (PNG, JPG, SVG):'}
                            </label>
                            <div style={{ position: 'relative' }}>
                                <input
                                    type="url"
                                    value={urlInput}
                                    onChange={(e) => setUrlInput(e.target.value)}
                                    placeholder="https://drive.google.com/file/d/... or direct image link"
                                    className="panel-input"
                                    required
                                    style={{ paddingLeft: '32px' }}
                                />
                                <Link size={14} style={{ position: 'absolute', left: '10px', top: '12px', color: '#64748b' }} />
                            </div>
                            <p style={{ fontSize: '11px', color: '#64748b', marginTop: '6px' }}>
                                Ensure link sharing is set to "Anyone with the link can view".
                            </p>
                        </div>

                        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '20px' }}>
                            <button type="button" className="btn-secondary" onClick={onClose}>
                                Cancel
                            </button>
                            <button type="submit" className="nav-cta-btn" disabled={importing}>
                                {importing ? 'Importing...' : 'Fetch & Place Artwork'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

