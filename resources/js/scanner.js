import { Html5Qrcode } from 'html5-qrcode';

function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        oscillator.start();
        oscillator.stop(ctx.currentTime + 0.12);
    } catch (e) {
        // audio not available (e.g. autoplay restrictions) -- non-fatal
    }
}

window.startBarcodeScanner = function (elementId, onScan) {
    const html5Qrcode = new Html5Qrcode(elementId);
    const config = { fps: 10, qrbox: { width: 250, height: 150 } };

    html5Qrcode
        .start(
            { facingMode: 'environment' },
            config,
            (decodedText) => {
                beep();
                onScan(decodedText);
            },
            () => {
                // per-frame decode failures are expected constantly while aiming; ignore
            },
        )
        .catch((err) => {
            console.error('Unable to start barcode scanner', err);
            onScan(null, err);
        });

    return html5Qrcode;
};
