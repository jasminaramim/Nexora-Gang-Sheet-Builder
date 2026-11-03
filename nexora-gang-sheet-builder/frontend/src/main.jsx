import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.jsx';

function mountNexoraBuilder(targetElementId, options = {}) {
    const el = typeof targetElementId === 'string' ? document.getElementById(targetElementId) : targetElementId;
    if (!el) return;

    const dataset = el.dataset || {};
    const productId = options.productId || parseInt(dataset.productId) || 0;
    const variationId = options.variationId || parseInt(dataset.variationId) || 0;
    const initialWidth = parseFloat(options.width || dataset.initialWidth || 22);
    const initialHeight = parseFloat(options.height || dataset.initialHeight || 60);
    const token = options.token || dataset.token || '';

    // If already mounted, unmount previous to refresh with selected variation
    if (el._reactRoot) {
        el._reactRoot.unmount();
    }

    const root = ReactDOM.createRoot(el);
    el._reactRoot = root;

    root.render(
        <React.StrictMode>
            <App
                initialProductId={productId}
                initialVariationId={variationId}
                initialWidth={initialWidth}
                initialHeight={initialHeight}
                initialToken={token}
            />
        </React.StrictMode>
    );

    return root;
}

// Expose globally for WordPress modal launcher
if (typeof window !== 'undefined') {
    window.nexoraMountBuilder = mountNexoraBuilder;
}

// Auto-mount on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // 1. Standalone / Shortcode container
    const rootEl = document.getElementById('nexora-gang-sheet-builder-root');
    if (rootEl) {
        mountNexoraBuilder(rootEl);
    }

    // 2. Dev mode standard #root
    const devRoot = document.getElementById('root');
    if (devRoot) {
        mountNexoraBuilder(devRoot);
    }
});

