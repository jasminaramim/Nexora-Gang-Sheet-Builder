/**
 * DPI Calculator & Quality Checker for DTF Gang Sheets
 */

export function calculateDpi(naturalWidthPx, naturalHeightPx, printWidthInches, printHeightInches) {
    if (!naturalWidthPx || !printWidthInches || printWidthInches <= 0) {
        return 300;
    }

    const horizontalDpi = Math.round(naturalWidthPx / printWidthInches);
    const verticalDpi = Math.round(naturalHeightPx / (printHeightInches || printWidthInches));

    // Return the minimum of horizontal or vertical DPI for conservative print estimation
    return Math.min(horizontalDpi, verticalDpi);
}

export function getDpiQuality(dpi) {
    if (dpi >= 300) {
        return {
            level: 'optimal',
            label: `${dpi} DPI (Optimal)`,
            color: '#10b981', // green
            bgColor: 'rgba(16, 185, 129, 0.15)',
            borderColor: '#10b981',
            text: 'Print-Ready Commercial Quality',
            isWarning: false
        };
    } else if (dpi >= 220) {
        return {
            level: 'good',
            label: `${dpi} DPI (Good)`,
            color: '#34d399',
            bgColor: 'rgba(52, 211, 153, 0.15)',
            borderColor: '#34d399',
            text: 'Good Quality Print',
            isWarning: false
        };
    } else if (dpi >= 150) {
        return {
            level: 'medium',
            label: `${dpi} DPI (Medium)`,
            color: '#f59e0b', // amber
            bgColor: 'rgba(245, 158, 11, 0.15)',
            borderColor: '#f59e0b',
            text: 'Acceptable resolution. May lose fine details.',
            isWarning: true
        };
    } else {
        return {
            level: 'low',
            label: `${dpi} DPI (Low Quality)`,
            color: '#ef4444', // red
            bgColor: 'rgba(239, 68, 68, 0.15)',
            borderColor: '#ef4444',
            text: 'Low Resolution! Image will print blurry or pixelated.',
            isWarning: true
        };
    }
}

