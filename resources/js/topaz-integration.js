/**
 * Topaz S460 Signature Pad Integration
 * 
 * This file provides functions for integrating the Topaz S460 signature pad
 * with the web application. It requires the SigWeb library from Topaz Systems.
 */

// Store the tablet state globally
let tabletState = false;

// Initialize Topaz SigWeb when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    if (window.SetTabletState) {
        initializeTopaz();
    } else {
        console.warn('Topaz SigWeb library not loaded. Make sure it is included in your page.');
    }
});

/**
 * Initialize the Topaz signature pad
 */
function initializeTopaz() {
    // Detect if the ActiveX/plugin is available
    try {
        // Try to set up the tablet
        window.SetTabletState(0, onSetupCallback);
        
        // Configure SigWeb for a nice display
        window.SetDisplayXSize(500);
        window.SetDisplayYSize(100);
        window.SetJustifyMode(0);
        window.SetImageXSize(500);
        window.SetImageYSize(100);
        window.SetImagePenWidth(4);
        window.SetTabletLogicalXSize(10000);
        window.SetTabletLogicalYSize(4000);
        window.SetTabletComPort("");

    } catch (e) {
        console.error('Error initializing Topaz SigWeb:', e);
    }
}

/**
 * Callback function for when the tablet state is set
 * 
 * @param {number} state - The state of the tablet (0=ready, 1=not ready)
 */
function onSetupCallback(state) {
    tabletState = (state === 0);
    
    if (tabletState) {
        console.info('Topaz signature pad is connected and ready.');
    } else {
        console.warn('Topaz signature pad is not connected or not ready.');
    }
    
    // Dispatch a custom event that our Alpine.js can listen for
    document.dispatchEvent(new CustomEvent('topaz-status-change', { 
        detail: { isReady: tabletState } 
    }));
}

/**
 * Clear the signature from the Topaz device
 */
function clearTopazSignature() {
    if (window.ClearTablet) {
        window.ClearTablet();
    }
}

/**
 * Capture the current signature from the Topaz device
 * 
 * @returns {string|null} The signature as a base64 data URL, or null if no signature
 */
function captureTopazSignature() {
    if (!tabletState || !window.GetSigString) {
        return null;
    }
    
    try {
        // Get the signature data
        const sigStringData = window.GetSigString();
        
        // Convert to base64 (if there is a signature)
        if (sigStringData) {
            const sigBase64 = window.SigWebToBase64(sigStringData);
            
            if (sigBase64 && sigBase64.length > 0) {
                return 'data:image/png;base64,' + sigBase64;
            }
        }
    } catch (e) {
        console.error('Error capturing signature from Topaz device:', e);
    }
    
    return null;
}

/**
 * Check if the Topaz device is connected and ready
 * 
 * @returns {boolean} True if the Topaz device is ready, false otherwise
 */
function isTopazReady() {
    return tabletState;
}

/**
 * Close the Topaz device connection
 * 
 * Call this when closing the signature page/modal to properly release the device
 */
function closeTopazDevice() {
    if (window.SetTabletState) {
        window.SetTabletState(0, null);
    }
}

// Make functions available globally
window.TopazSignature = {
    initialize: initializeTopaz,
    clear: clearTopazSignature,
    capture: captureTopazSignature,
    isReady: isTopazReady,
    close: closeTopazDevice
};