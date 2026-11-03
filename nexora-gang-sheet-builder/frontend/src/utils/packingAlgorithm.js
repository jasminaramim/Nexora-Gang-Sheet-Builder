/**
 * Intelligent 2D Auto Nesting & Bin Packing Algorithm for DTF Gang Sheets
 * Arranges placed images to maximize sheet utilization and minimize wasted space.
 */

export function autoNestObjects(objects, sheetWidth, sheetHeight, spacing = 0.25, margin = 0.25, allowRotation = false) {
    if (!objects || objects.length === 0) {
        return { packed: [], utilization: 0, overflow: [] };
    }

    const printableWidth = sheetWidth - (margin * 2);
    const printableHeight = sheetHeight - (margin * 2);

    // Prepare items with IDs and dimensions
    const items = objects.map((obj, index) => {
        const w = (obj.width * (obj.scaleX || 1));
        const h = (obj.height * (obj.scaleY || 1));
        return {
            id: obj.id || `item_${index}`,
            ref: obj,
            width: w,
            height: h,
            rotated: false,
            area: w * h,
        };
    });

    // Sort items by height descending (Shelf First Fit Decreasing)
    items.sort((a, b) => b.height - a.height);

    let currentX = margin;
    let currentY = margin;
    let currentShelfHeight = 0;
    const packed = [];
    const overflow = [];

    for (let item of items) {
        let itemW = item.width;
        let itemH = item.height;

        // Check if rotation is allowed and helps fit
        if (allowRotation && (itemW > printableWidth - (currentX - margin)) && itemH <= printableWidth - (currentX - margin)) {
            // Swap width & height for 90 degree rotation
            itemW = item.height;
            itemH = item.width;
            item.rotated = true;
        }

        // Check if item fits in current shelf (row)
        if (currentX + itemW <= sheetWidth - margin) {
            packed.push({
                ...item,
                x: currentX,
                y: currentY,
                finalWidth: itemW,
                finalHeight: itemH,
            });
            currentX += itemW + spacing;
            currentShelfHeight = Math.max(currentShelfHeight, itemH);
        } else {
            // Start a new shelf (next row down)
            currentX = margin;
            currentY += currentShelfHeight + spacing;
            currentShelfHeight = itemH;

            // Check if fits on the new row within sheet height
            if (currentY + itemH <= sheetHeight - margin) {
                packed.push({
                    ...item,
                    x: currentX,
                    y: currentY,
                    finalWidth: itemW,
                    finalHeight: itemH,
                });
                currentX += itemW + spacing;
            } else {
                // Item overflows the current sheet dimensions
                overflow.push(item);
            }
        }
    }

    // Calculate total utilization percentage
    const totalSheetArea = sheetWidth * sheetHeight;
    const usedArea = packed.reduce((acc, curr) => acc + (curr.width * curr.height), 0);
    const utilization = totalSheetArea > 0 ? Math.min(100, Math.round((usedArea / totalSheetArea) * 1000) / 10) : 0;

    return {
        packed,
        overflow,
        utilization,
        usedArea: Math.round(usedArea * 10) / 10,
        wastedArea: Math.max(0, Math.round((totalSheetArea - usedArea) * 10) / 10),
    };
}

