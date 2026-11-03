import { jsPDF } from 'jspdf';

/**
 * High-Resolution 300 DPI Export Engine for DTF Gang Sheets
 */

/**
 * Generate 300 DPI High-Resolution Transparent PNG
 */
export async function generate300DpiPng(fabricCanvas, sheetWidthInches, sheetHeightInches, targetDpi = 300) {
    if (!fabricCanvas) return null;

    // Target dimensions in pixels at target DPI
    const targetWidthPx = Math.round(sheetWidthInches * targetDpi);
    const targetHeightPx = Math.round(sheetHeightInches * targetDpi);

    // Current canvas dimensions
    const currentW = fabricCanvas.getWidth();
    const currentH = fabricCanvas.getHeight();

    // Multiplier for export
    const multiplier = targetWidthPx / currentW;

    // Temporarily disable grid/rulers if drawn on canvas
    const prevBgColor = fabricCanvas.backgroundColor;
    fabricCanvas.backgroundColor = ''; // transparent

    // Deselect active object so selection handles are not in export
    fabricCanvas.discardActiveObject();
    fabricCanvas.renderAll();

    // Export data URL with multiplier
    const dataUrl = fabricCanvas.toDataURL({
        format: 'png',
        quality: 1,
        multiplier: multiplier,
        enableRetinaScaling: false,
    });

    // Restore background
    fabricCanvas.backgroundColor = prevBgColor;
    fabricCanvas.renderAll();

    return {
        dataUrl,
        widthPx: targetWidthPx,
        heightPx: targetHeightPx,
        dpi: targetDpi,
    };
}

/**
 * Generate Print-Ready PDF with exact physical dimensions in inches
 */
export async function generatePrintPdf(fabricCanvas, sheetWidthInches, sheetHeightInches, filename = 'nexora-gang-sheet.pdf') {
    if (!fabricCanvas) return null;

    const pngExport = await generate300DpiPng(fabricCanvas, sheetWidthInches, sheetHeightInches, 300);
    if (!pngExport) return null;

    // Create jsPDF document with physical inches
    const orientation = sheetWidthInches > sheetHeightInches ? 'landscape' : 'portrait';
    const pdf = new jsPDF({
        orientation: orientation,
        unit: 'in',
        format: [sheetWidthInches, sheetHeightInches],
        compress: true,
    });

    // Add high-res image 1:1 on the page
    pdf.addImage(
        pngExport.dataUrl,
        'PNG',
        0,
        0,
        sheetWidthInches,
        sheetHeightInches,
        undefined,
        'FAST'
    );

    // Download PDF directly
    pdf.save(filename);

    return {
        pdfBlob: pdf.output('blob'),
        filename,
    };
}

/**
 * Download high-res PNG file directly to customer machine
 */
export function downloadPngFile(dataUrl, filename = 'nexora-gang-sheet-300dpi.png') {
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

