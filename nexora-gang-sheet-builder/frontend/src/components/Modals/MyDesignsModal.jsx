import React, { useEffect, useState } from 'react';
import { FolderHeart, X, Trash2, ArrowRight, Clock } from 'lucide-react';
import { nexoraConfig } from '../../utils/api';

export default function MyDesignsModal({
    isOpen,
    onClose,
    onLoadDesign,
}) {
    const [designs, setDesigns] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!isOpen) return;

        setLoading(true);
        // Try fetching from REST API or localStorage
        fetch(`${nexoraConfig.rest_url}designs`)
            .then((r) => (r.ok ? r.json() : null))
            .then((data) => {
                if (data && data.designs && data.designs.length > 0) {
                    setDesigns(data.designs);
                } else {
                    const local = JSON.parse(localStorage.getItem('nexora_saved_designs') || '[]');
                    setDesigns(local);
                }
            })
            .catch(() => {
                const local = JSON.parse(localStorage.getItem('nexora_saved_designs') || '[]');
                setDesigns(local);
            })
            .finally(() => setLoading(false));
    }, [isOpen]);

    if (!isOpen) return null;

    const handleDelete = (id, e) => {
        e.stopPropagation();
        const updated = designs.filter((d) => d.id !== id);
        setDesigns(updated);
        localStorage.setItem('nexora_saved_designs', JSON.stringify(updated));
    };

    return (
        <div className="nexora-modal-backdrop">
            <div className="nexora-modal-content" style={{ maxWidth: '620px' }}>
                <div className="modal-header">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <FolderHeart size={18} color="#ec4899" />
                        <h3 style={{ margin: 0, fontSize: '15px', color: '#fff' }}>My Saved Gang Sheet Designs</h3>
                    </div>
                    <button type="button" onClick={onClose} style={{ background: 'transparent', border: 'none', color: '#94a3b8', cursor: 'pointer' }}>
                        <X size={18} />
                    </button>
                </div>

                <div className="modal-body">
                    {loading ? (
                        <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8' }}>
                            Loading saved designs...
                        </div>
                    ) : designs.length === 0 ? (
                        <div style={{ textAlign: 'center', padding: '30px 10px', color: '#64748b', fontSize: '13px' }}>
                            <FolderHeart size={36} style={{ margin: '0 auto 10px', opacity: 0.4 }} />
                            You haven't saved any designs yet. Click "Save Draft" in the bottom bar to preserve your work.
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                            {designs.map((d) => (
                                <div
                                    key={d.id}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        background: '#0f172a',
                                        border: '1px solid #1e293b',
                                        borderRadius: '8px',
                                        padding: '10px 14px',
                                        cursor: 'pointer',
                                    }}
                                    onClick={() => {
                                        onLoadDesign(d);
                                        onClose();
                                    }}
                                >
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                        {d.thumbnail_url ? (
                                            <img src={d.thumbnail_url} alt="" style={{ width: '48px', height: '48px', objectFit: 'contain', background: '#1e293b', borderRadius: '4px' }} />
                                        ) : (
                                            <div style={{ width: '48px', height: '48px', background: '#1e293b', borderRadius: '4px' }} />
                                        )}
                                        <div>
                                            <div style={{ fontWeight: 600, color: '#f8fafc', fontSize: '13px' }}>{d.name}</div>
                                            <div style={{ fontSize: '11px', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '6px', marginTop: '2px' }}>
                                                <span>Size: {d.width}" × {d.height}"</span>
                                                <span>&bull;</span>
                                                <Clock size={11} />
                                                <span>{d.created_at ? new Date(d.created_at).toLocaleDateString() : 'Recent'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                        <button
                                            type="button"
                                            onClick={(e) => handleDelete(d.id, e)}
                                            style={{ background: 'transparent', border: 'none', color: '#ef4444', cursor: 'pointer', padding: '6px' }}
                                            title="Delete saved design"
                                        >
                                            <Trash2 size={15} />
                                        </button>
                                        <button
                                            type="button"
                                            className="nav-cta-btn"
                                            style={{ padding: '6px 12px', fontSize: '12px' }}
                                        >
                                            <span>Open</span>
                                            <ArrowRight size={13} />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
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

