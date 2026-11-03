import React, { useEffect, useRef } from 'react';
import { calculateDpi, getDpiQuality } from '../../utils/dpiChecker';
import { AlertCircle, CheckCircle2 } from 'lucide-react';

export default function CanvasWorkspace({
    canvasRef,
    sheetWidth,
    sheetHeight,
    unit = 'in',
    margin = 0.25,
    zoom = 1,
    canvasBg = 'checkerboard', // 'checkerboard', 'white', 'dark'
    selectedObject,
    PPI,
}) {
    const viewportRef = useRef(null);

    // Calculate DPI of currently active selected object
    let selectedDpiInfo = null;
    let isOutOfBounds = false;

    if (selectedObject && PPI) {
        const objW = (selectedObject.width * (selectedObject.scaleX || 1)) / PPI;
        const objH = (selectedObject.height * (selectedObject.scaleY || 1)) / PPI;
        const naturalW = selectedObject._element ? selectedObject._element.naturalWidth : (selectedObject.naturalWidth || 1000);
        const naturalH = selectedObject._element ? selectedObject._element.naturalHeight : (selectedObject.naturalHeight || 1000);

        const dpi = calculateDpi(naturalW, naturalH, objW, objH);
        const quality = getDpiQuality(dpi);

        // Check boundary
        const leftIn = (selectedObject.left || 0) / PPI;
        const topIn = (selectedObject.top || 0) / PPI;
        if (leftIn < margin || topIn < margin || leftIn + objW > sheetWidth - margin || topIn + objH > sheetHeight - margin) {
            isOutOfBounds = true;
        }

        selectedDpiInfo = {
            widthIn: Math.round(objW * 10) / 10,
            heightIn: Math.round(objH * 10) / 10,
            dpi,
            quality,
        };
    }

    return (
        <div className="canvas-viewport-container" ref={viewportRef}>
            {/* The Scaled Gang Sheet Area */}
            <div
                className={`gang-sheet-canvas-wrapper ${
                    canvasBg === 'white' ? 'white-bg' : canvasBg === 'dark' ? 'dark-bg' : 'checkerboard-bg'
                }`}
                style={{
                    transform: `scale(${zoom})`,
                    transformOrigin: 'center center',
                }}
            >
                {/* Top Width Dimension Label */}
                <div className="canvas-dim-badge-w">
                    Width: {sheetWidth} {unit}
                </div>

                {/* Left Height Dimension Label */}
                <div className="canvas-dim-badge-h">
                    Height: {sheetHeight} {unit}
                </div>

                {/* Printable Safety Margin Dashed Line */}
                <div
                    className="sheet-safety-margin-border"
                    style={{
                        top: `${margin * PPI}px`,
                        left: `${margin * PPI}px`,
                        right: `${margin * PPI}px`,
                        bottom: `${margin * PPI}px`,
                    }}
                />

                {/* Fabric HTML5 Canvas */}
                <canvas ref={canvasRef} />
            </div>

            {/* Selected Object Real-Time DPI & Margin Warning Banner */}
            {selectedDpiInfo && (
                <div
                    style={{
                        position: 'absolute',
                        bottom: '20px',
                        left: '50%',
                        transform: 'translateX(-50%)',
                        background: 'rgba(15, 23, 42, 0.95)',
                        border: `1px solid ${isOutOfBounds ? '#ef4444' : selectedDpiInfo.quality.borderColor}`,
                        borderRadius: '8px',
                        padding: '8px 16px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        boxShadow: '0 8px 24px rgba(0, 0, 0, 0.5)',
                        zIndex: 30,
                        backdropFilter: 'blur(8px)',
                    }}
                >
                    {selectedDpiInfo.quality.isWarning || isOutOfBounds ? (
                        <AlertCircle size={18} color={isOutOfBounds ? '#ef4444' : selectedDpiInfo.quality.color} />
                    ) : (
                        <CheckCircle2 size={18} color="#10b981" />
                    )}

                    <div style={{ fontSize: '12px' }}>
                        <span style={{ fontWeight: 700, color: '#fff', marginRight: '8px' }}>
                            Size: {selectedDpiInfo.widthIn}" × {selectedDpiInfo.heightIn}"
                        </span>
                        <span
                            style={{
                                color: selectedDpiInfo.quality.color,
                                fontWeight: 700,
                                background: selectedDpiInfo.quality.bgColor,
                                padding: '2px 6px',
                                borderRadius: '4px',
                            }}
                        >
                            {selectedDpiInfo.quality.label}
                        </span>
                        {isOutOfBounds && (
                            <span style={{ color: '#ef4444', fontWeight: 700, marginLeft: '8px' }}>
                                &bull; Warning: Design outside printable margins!
                            </span>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

