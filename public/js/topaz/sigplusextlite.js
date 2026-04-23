/**
 * TopazSigPlusExtLite - A JavaScript wrapper for the Topaz SigPlusExtLite API
 * This file provides a promise-based interface to the Topaz signature pad.
 *
 * Create this file at: public/js/topaz/sigplusextlite.js
 */

class TopazSigPlusExtLite {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.width = 0;
        this.height = 0;
        this.isTabletOn = false;
        this.hasSignature = false;
        this.sigData = null;

        // Tablet properties
        this.tabletComPort = "HID1"; // Default USB port
        this.inkColor = "#000000";   // Black ink
        this.inkWidth = 2;           // Ink width in pixels

        // Mock data for development/testing without hardware
        this.isDemoMode = false;

        // Event tracking
        this.isDrawing = false;
        this.lastX = 0;
        this.lastY = 0;
        this.points = [];
    }

    /**
     * Initialize the signature component
     * @param {HTMLCanvasElement} canvasElement - The canvas element to draw on
     * @returns {Promise} - Resolves when initialization is complete
     */
    async initialize(canvasElement) {
        return new Promise((resolve, reject) => {
            try {
                // Store canvas reference
                this.canvas = canvasElement;
                this.ctx = this.canvas.getContext('2d');

                // Set canvas size to match its display size
                this.resizeCanvas();

                // Check if SigPlusExtLite is available (would be true with actual Topaz SDK)
                // For this implementation, we'll use a fallback mode for development
                try {
                    // Check for actual SigPlusExtLite API
                    // This would be the check for the real API
                    // if (typeof window.SigPlusExtLite === 'undefined') { ... }

                    // For demo purposes, we're always using our implementation
                    console.log("Using SigPlusExtLite implementation");
                    this.setupCanvasEvents();
                    this.isDemoMode = true;
                } catch (e) {
                    console.warn("SigPlusExtLite API not detected, using canvas fallback:", e);
                    this.setupCanvasEvents();
                    this.isDemoMode = true;
                }

                // Handle window resize
                window.addEventListener('resize', () => this.resizeCanvas());

                resolve();
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Resize the canvas to match its display size
     */
    resizeCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
        this.width = rect.width;
        this.height = rect.height;

        // Redraw signature if we have one
        if (this.hasSignature && this.points.length > 0) {
            this.redrawSignature();
        }
    }

    /**
     * Set up events for canvas-based signature capture
     * This is used when the actual Topaz hardware isn't available
     */
    setupCanvasEvents() {
        // Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.handlePointerDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.handlePointerMove(e));
        this.canvas.addEventListener('mouseup', () => this.handlePointerUp());
        this.canvas.addEventListener('mouseout', () => this.handlePointerUp());

        // Touch events for mobile
        this.canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            this.handlePointerDown(mouseEvent);
        });

        this.canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            this.handlePointerMove(mouseEvent);
        });

        this.canvas.addEventListener('touchend', () => {
            this.handlePointerUp();
        });
    }

    /**
     * Handle pointer down event
     * @param {MouseEvent} e - The mouse event
     */
    handlePointerDown(e) {
        if (!this.isTabletOn) return;

        const rect = this.canvas.getBoundingClientRect();
        this.lastX = e.clientX - rect.left;
        this.lastY = e.clientY - rect.top;
        this.isDrawing = true;

        // Start a new stroke
        this.points.push({
            type: 'start',
            x: this.lastX,
            y: this.lastY
        });
    }

    /**
     * Handle pointer move event
     * @param {MouseEvent} e - The mouse event
     */
    handlePointerMove(e) {
        if (!this.isDrawing || !this.isTabletOn) return;

        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Draw line
        this.ctx.beginPath();
        this.ctx.moveTo(this.lastX, this.lastY);
        this.ctx.lineTo(x, y);
        this.ctx.strokeStyle = this.inkColor;
        this.ctx.lineWidth = this.inkWidth;
        this.ctx.lineCap = 'round';
        this.ctx.stroke();

        // Save the point
        this.points.push({
            type: 'move',
            x: x,
            y: y
        });

        this.lastX = x;
        this.lastY = y;
        this.hasSignature = true;
    }

    /**
     * Handle pointer up event
     */
    handlePointerUp() {
        if (this.isDrawing && this.isTabletOn) {
            this.isDrawing = false;
            // End the stroke
            this.points.push({
                type: 'end'
            });
        }
    }

    /**
     * Redraw the signature from saved points
     */
    redrawSignature() {
        if (!this.points || this.points.length === 0) return;

        this.ctx.clearRect(0, 0, this.width, this.height);
        this.ctx.strokeStyle = this.inkColor;
        this.ctx.lineWidth = this.inkWidth;
        this.ctx.lineCap = 'round';

        let lastX, lastY;

        for (let i = 0; i < this.points.length; i++) {
            const point = this.points[i];

            if (point.type === 'start') {
                lastX = point.x;
                lastY = point.y;
            } else if (point.type === 'move') {
                this.ctx.beginPath();
                this.ctx.moveTo(lastX, lastY);
                this.ctx.lineTo(point.x, point.y);
                this.ctx.stroke();

                lastX = point.x;
                lastY = point.y;
            }
        }
    }

    /**
     * Set the tablet state (on/off)
     * @param {boolean} isOn - Whether the tablet should be on
     * @returns {Promise} - Resolves when complete
     */
    async setTabletState(isOn) {
        return new Promise((resolve) => {
            this.isTabletOn = isOn;
            resolve(true);
        });
    }

    /**
     * Set the tablet COM port
     * @param {string} port - The COM port to use
     * @returns {Promise} - Resolves when complete
     */
    async setTabletComPort(port) {
        return new Promise((resolve) => {
            this.tabletComPort = port;
            resolve(true);
        });
    }

    /**
     * Check if the tablet is connected
     * @returns {Promise<boolean>} - Resolves with connection status
     */
    async isTabletConnected() {
        return new Promise((resolve) => {
            // In the real implementation, this would check hardware
            // For our implementation, we'll return true if in demo mode
            resolve(this.isDemoMode);
        });
    }

    /**
     * Clear the signature
     * @returns {Promise} - Resolves when complete
     */
    async clearSignature() {
        return new Promise((resolve) => {
            if (this.ctx) {
                this.ctx.clearRect(0, 0, this.width, this.height);
            }

            this.hasSignature = false;
            this.points = [];
            this.sigData = null;

            resolve(true);
        });
    }

    /**
     * Check if the signature pad is empty
     * @returns {Promise<boolean>} - Resolves with empty status
     */
    async isEmpty() {
        return new Promise((resolve) => {
            resolve(!this.hasSignature);
        });
    }

    /**
     * Get the signature as a base64 encoded PNG image
     * @returns {Promise<string>} - Resolves with base64 image data
     */
    async getSigImage() {
        return new Promise((resolve) => {
            if (!this.hasSignature || !this.canvas) {
                resolve(null);
                return;
            }

            // Get base64 data from canvas, remove data URL prefix
            const dataUrl = this.canvas.toDataURL('image/png');
            const base64Data = dataUrl.replace('data:image/png;base64,', '');

            this.sigData = base64Data;
            resolve(base64Data);
        });
    }
}
