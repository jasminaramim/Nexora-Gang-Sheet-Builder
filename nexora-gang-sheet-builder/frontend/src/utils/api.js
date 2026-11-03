/**
 * API service for Nexora Gang Sheet Builder
 * Communicates with WordPress REST API or provides graceful fallback for standalone mode.
 */

const getApiConfig = () => {
    if (typeof window !== 'undefined' && window.nexoraBuilderData) {
        return window.nexoraBuilderData;
    }
    return {
        rest_url: '/wp-json/nexora/v1/',
        nonce: '',
        currency: '$',
        cart_url: '/cart',
        checkout_url: '/checkout',
        is_logged_in: false,
        user_name: 'Guest',
        presets: [
            { id: '22x24', name: 'Small Sheet (22" x 24")', width: 22, height: 24, unit: 'in', price: 24.99, is_default: false },
            { id: '22x36', name: 'Standard Sheet (22" x 36")', width: 22, height: 36, unit: 'in', price: 34.99, is_default: false },
            { id: '22x60', name: 'Popular Sheet (22" x 60")', width: 22, height: 60, unit: 'in', price: 54.99, is_default: true },
            { id: '22x120', name: 'Large Sheet (22" x 120")', width: 22, height: 120, unit: 'in', price: 99.99, is_default: false },
            { id: '22x240', name: 'Roll 20ft (22" x 240")', width: 22, height: 240, unit: 'in', price: 189.99, is_default: false },
            { id: '13x19', name: 'Desktop Sheet (13" x 19")', width: 13, height: 19, unit: 'in', price: 14.99, is_default: false },
        ],
        pricing: {
            pricing_mode: 'tiered',
            price_per_sq_ft: 7.50,
            price_per_sq_in: 0.052,
            min_price: 15.00,
            tiered_pricing: [
                { min_sqft: 1, max_sqft: 5, rate: 8.50 },
                { min_sqft: 6, max_sqft: 15, rate: 7.20 },
                { min_sqft: 16, max_sqft: 30, rate: 6.50 },
                { min_sqft: 31, max_sqft: 9999, rate: 5.50 },
            ]
        },
        general: {
            unit: 'in',
            default_dpi: 300,
            min_dpi_warning: 250,
            default_sheet_width: 22,
            default_sheet_height: 60,
            print_margin: 0.25,
            dark_mode: true
        },
        cloud: {
            google_drive_enabled: true,
            dropbox_enabled: true
        }
    };
};

export const nexoraConfig = getApiConfig();

export async function fetchBuilderConfig() {
    try {
        const res = await fetch(`${nexoraConfig.rest_url}config`);
        if (res.ok) {
            const data = await res.json();
            return data;
        }
    } catch (e) {
        console.warn('Nexora API fallback to local defaults:', e);
    }
    return { success: true, ...nexoraConfig };
}

export async function fetchProducts() {
    try {
        const res = await fetch(`${nexoraConfig.rest_url}products`);
        if (res.ok) {
            const data = await res.json();
            return data.products || [];
        }
    } catch (e) {
        console.warn('Using default products list');
    }
    return [
        {
            id: 101,
            name: 'Custom DTF Gang Sheet (22" Width)',
            price: 35.00,
            price_html: '$35.00 - $189.99',
            is_variable: true,
            variations: [
                { id: 1011, attributes: { size: '22x24' }, price: 24.99, display_price_html: '$24.99' },
                { id: 1012, attributes: { size: '22x36' }, price: 34.99, display_price_html: '$34.99' },
                { id: 1013, attributes: { size: '22x60' }, price: 54.99, display_price_html: '$54.99' },
                { id: 1014, attributes: { size: '22x120' }, price: 99.99, display_price_html: '$99.99' }
            ]
        },
        {
            id: 102,
            name: 'DTF Gang Sheet By The Foot ($7.50 / linear ft)',
            price: 7.50,
            price_html: '$7.50 / linear ft',
            is_variable: false,
            variations: []
        }
    ];
}

export async function uploadArtworkFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch(`${nexoraConfig.rest_url}upload`, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': nexoraConfig.nonce || '',
            },
            body: formData,
        });

        if (res.ok) {
            const data = await res.json();
            return data.media;
        }
    } catch (e) {
        console.warn('Server upload fallback to local ObjectURL:', e);
    }

    // Graceful fallback for offline / standalone preview
    return new Promise((resolve) => {
        const objectUrl = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            resolve({
                id: Date.now() + Math.random(),
                name: file.name,
                url: objectUrl,
                width: img.naturalWidth,
                height: img.naturalHeight,
                dpi: 300,
                size: file.size,
            });
        };
        img.onerror = () => {
            resolve({
                id: Date.now(),
                name: file.name,
                url: objectUrl,
                width: 1000,
                height: 1000,
                dpi: 300,
                size: file.size,
            });
        };
        img.src = objectUrl;
    });
}

export async function fetchUserMedia() {
    try {
        const res = await fetch(`${nexoraConfig.rest_url}media`);
        if (res.ok) {
            const data = await res.json();
            return data.media || [];
        }
    } catch (e) {
        console.warn('Could not fetch server media, using local session');
    }
    return [];
}

export async function saveGangSheet(sheetData) {
    try {
        const res = await fetch(`${nexoraConfig.rest_url}sheets/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nexoraConfig.nonce || '',
            },
            body: JSON.stringify(sheetData),
        });

        if (res.ok) {
            return await res.json();
        }
    } catch (e) {
        console.warn('Save gang sheet server fallback:', e);
    }

    // Fallback store in localStorage
    const savedSheets = JSON.parse(localStorage.getItem('nexora_saved_sheets') || '[]');
    const token = sheetData.design_token || 'nxr-' + Date.now();
    savedSheets.unshift({ ...sheetData, design_token: token, id: Date.now() });
    localStorage.setItem('nexora_saved_sheets', JSON.stringify(savedSheets.slice(0, 20)));

    return {
        success: true,
        design_token: token,
        sheet_id: Date.now(),
    };
}

export async function addToCartAndCheckout(cartPayload) {
    const ajaxUrl = nexoraConfig.ajax_url || (typeof window !== 'undefined' && window.nexoraBuilderData && window.nexoraBuilderData.ajax_url) || '/wp-admin/admin-ajax.php';
    
    // Tier 1: Call WordPress AJAX with credentials (sets session cookies automatically)
    try {
        const ajaxRes = await fetch(`${ajaxUrl}?action=nexora_add_to_cart`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(cartPayload),
        });

        if (ajaxRes.ok) {
            const data = await ajaxRes.json();
            if (data && data.success && data.data) {
                return {
                    success: true,
                    cart_url: data.data.cart_url || nexoraConfig.cart_url || '/cart/',
                    fallback_direct_url: data.data.fallback_direct_url || data.data.cart_url,
                    redirect_url: data.data.cart_url || nexoraConfig.cart_url || '/cart/',
                    ...data.data
                };
            }
        }
    } catch (e) {
        console.warn('AJAX add to cart failed, trying REST endpoint:', e);
    }

    // Tier 2: Call REST API endpoint with credentials
    try {
        const res = await fetch(`${nexoraConfig.rest_url}cart/add`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nexoraConfig.nonce || '',
            },
            body: JSON.stringify(cartPayload),
        });

        if (res.ok) {
            const data = await res.json();
            if (data && data.success) {
                return data;
            }
        }
    } catch (e) {
        console.warn('REST add to cart failed:', e);
    }

    // Tier 3: Native WooCommerce Direct Link Fallback (?add-to-cart=PID&nexora_token=TOKEN)
    // First save the sheet to obtain a persistent token
    const saved = await saveGangSheet(cartPayload);
    const token = (saved && saved.design_token) ? saved.design_token : ('nxr-' + Date.now());
    const prodId = cartPayload.product_id || (window.nexoraBuilderData ? window.nexoraBuilderData.default_product_id : 1) || 1;
    const baseCart = nexoraConfig.cart_url || '/cart/';
    const directUrl = `${baseCart}${baseCart.includes('?') ? '&' : '?'}add-to-cart=${prodId}&nexora_token=${token}`;

    return {
        success: true,
        cart_url: directUrl,
        fallback_direct_url: directUrl,
        redirect_url: directUrl,
    };
}

