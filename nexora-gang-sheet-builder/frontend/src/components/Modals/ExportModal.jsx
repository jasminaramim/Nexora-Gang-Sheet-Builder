import React, { useState } from 'react';
import { Download, FileText, Image as ImageIcon, X, CheckCircle, ShieldCheck } from 'lucide-react';
import { generate300DpiPng, generatePrintPdf, downloadPngFile } from '../../utils/dtfExporter';

export default function ExportModal({
    isOpen,
    onClose,
    fabricInstance,
    sheetWidth,
    sheetHeight,
}) {
    const [exportType, setExportType] = useState('png'); // 'png' or 'pdf'
    const [isExporting, setIsExporting] = useState(false);
    const [exportSuccess, setExportSuccess] = useState(false);

    if (!isOpen) return null;

    const handleRunExport = async () => {
        if (!fabricInstance.current) return;

        setIsExporting(true);
        setExportSuccess(false);

        try {
            if (exportType === 'png') {
                const result = await generate300DpiPng(fabricInstance.current, sheetWidth, sheetHeight, 300);
                if (result) {
                    downloadPngFile(result.dataUrl, `nexora-gang-sheet-${sheetWidth}x${sheetHeight}-300dpi.png`);
                    setExportSuccess(true);
                }
            } else {
                await generatePrintPdf(fabricInstance.current, sheetWidth, sheetHeight, `nexora-gang-sheet-${sheetWidth}x${sheetHeight}-print.pdf`);
                setExportSuccess(true);
            }
        } catch (e) {
            console.error('Export error:', e);
            alert('Failed to generate export file. Please check image permissions.');
        } finally {
            setIsExporting(false);
        }
    };

    return (
        <div className="nexora-modal-backdrop">
            <div className="nexora-modal-content">
                <div className="modal-header">
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <Download size={18} color="#7c3aed" />
                        <h3 style={{ margin: 0, fontSize: '15px', color: '#fff' }}>Export Print-Ready 300 DPI Gang Sheet</h3>
                    </div>
                    <button type="button" onClick={onClose} style={{ background: 'transparent', border: 'none', color: '#94a3b8', cursor: 'pointer' }}>
                        <X size={18} />
                    </button>
                </div>

                <div className="modal-body">
                    <div style={{ display: 'flex', gap: '10px', marginBottom: '16px' }}>
                        <div
                            onClick={() => setExportType('png')}
                            style={{
                                flex: 1,
                                padding: '14px',
                                borderRadius: '8px',
                                border: `2px solid ${exportType === 'png' ? '#7c3aed' : '#263143'}`,
                                background: exportType === 'png' ? 'rgba(124, 58, 237, 0.1)' : '#181f2c',
                                cursor: 'pointer',
                                textAlign: 'center',
                            }}
                        >
                            <ImageIcon size={24} color="#a78bfa" style={{ margin: '0 auto 6px' }} />
                            <div style={{ fontWeight: 700, fontSize: '13px', color: '#fff' }}>Transparent PNG</div>
                            <div style={{ fontSize: '11px', color: '#94a3b8' }}>300 DPI &bull; Lossless Alpha</div>
                        </div>

                        <div
                            onClick={() => setExportType('pdf')}
                            style={{
                                flex: 1,
                                padding: '14px',
                                borderRadius: '8px',
                                border: `2px solid ${exportType === 'pdf' ? '#7c3aed' : '#263143'}`,
                                background: exportType === 'pdf' ? 'rgba(124, 58, 237, 0.1)' : '#181f2c',
                                cursor: 'pointer',
                                textAlign: 'center',
                            }}
                        >
                            <FileText size={24} color="#38bdf8" style={{ margin: '0 auto 6px' }} />
                            <div style={{ fontWeight: 700, fontSize: '13px', color: '#fff' }}>Vector PDF</div>
                            <div style={{ fontSize: '11px', color: '#94a3b8' }}>Exact Physical Sheet Size</div>
                        </div>
                    </div>

                    <div style={{ background: '#0f172a', padding: '12px', borderRadius: '8px', border: '1px solid #1e293b', marginBottom: '16px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#10b981', fontSize: '12px', fontWeight: 600, marginBottom: '6px' }}>
                            <ShieldCheck size={16} />
                            <span>Commercial Production Verified</span>
                        </div>
                        <ul style={{ margin: 0, paddingLeft: '18px', fontSize: '11px', color: '#94a3b8' }}>
                            <li>Dimensions: {sheetWidth} inches × {sheetHeight} inches</li>
                            <li>Native Resolution: {Math.round(sheetWidth * 300)} × {Math.round(sheetHeight * 300)} pixels (300 DPI)</li>
                            <li>Alpha channel transparency preserved for Direct-to-Film RIP software</li>
                        </ul>
                    </div>

                    {exportSuccess && (
                        <div style={{ background: 'rgba(16, 185, 129, 0.1)', border: '1px solid #10b981', color: '#10b981', padding: '10px', borderRadius: '8px', display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12px' }}>
                            <CheckCircle size={16} />
                            <span>File exported and downloaded to your browser!</span>
                        </div>
                    )}
                </div>

                <div className="modal-footer">
                    <button type="button" className="btn-secondary" onClick={onClose}>
                        Close
                    </button>
                    <button
                        type="button"
                        className="nav-cta-btn"
                        onClick={handleRunExport}
                        disabled={isExporting}
                    >
                        <Download size={15} />
                        <span>{isExporting ? 'Rendering 300 DPI File...' : `Download ${exportType.toUpperCase()}`}</span>
                    </button>
                </div>
            </div>
        </div>
    );
}

