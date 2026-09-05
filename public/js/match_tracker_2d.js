/**
 * MatchTracker2D - Canvas/SVG Visualizer for Live Sports Events
 * Renderiza la cancha 2D interactiva con animación de balones, saques de esquina y ataques en vivo.
 */

class MatchTracker2D {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        this.ballPos = { x: this.width / 2, y: this.height / 2 };
        this.targetPos = { x: this.width / 2, y: this.height / 2 };
        this.currentStatusText = 'Inicio de Partido';

        this.init();
    }

    init() {
        this.drawPitch();
        this.animate();
    }

    drawPitch() {
        const ctx = this.ctx;
        const w = this.width;
        const h = this.height;

        // Césped con gradiente neón verde oscuro
        const bgGrad = ctx.createLinearGradient(0, 0, w, h);
        bgGrad.addColorStop(0, '#0d2818');
        bgGrad.addColorStop(1, '#05190e');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, w, h);

        // Líneas blancas del campo
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.lineWidth = 2;

        // Borde exterior
        ctx.strokeRect(10, 10, w - 20, h - 20);

        // Línea de medio campo
        ctx.beginPath();
        ctx.moveTo(w / 2, 10);
        ctx.lineTo(w / 2, h - 10);
        ctx.stroke();

        // Círculo central
        ctx.beginPath();
        ctx.arc(w / 2, h / 2, 35, 0, Math.PI * 2);
        ctx.stroke();

        // Áreas de penalti
        ctx.strokeRect(10, h / 2 - 45, 60, 90);
        ctx.strokeRect(w - 70, h / 2 - 45, 60, 90);

        // Texto de Estado del Partido
        ctx.fillStyle = '#00ff88';
        ctx.font = 'bold 13px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(this.currentStatusText.toUpperCase(), w / 2, 30);
    }

    updateEventStatus(statusText, targetXPercent, targetYPercent) {
        this.currentStatusText = statusText;
        this.targetPos.x = 10 + (this.width - 20) * (targetXPercent / 100);
        this.targetPos.y = 10 + (this.height - 20) * (targetYPercent / 100);
    }

    animate() {
        // Suavizado de posición del balón (Lerp)
        this.ballPos.x += (this.targetPos.x - this.ballPos.x) * 0.1;
        this.ballPos.y += (this.targetPos.y - this.ballPos.y) * 0.1;

        this.drawPitch();

        // Dibujar Balón Neón
        const ctx = this.ctx;
        ctx.beginPath();
        ctx.arc(this.ballPos.x, this.ballPos.y, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#00e5ff';
        ctx.shadowColor = '#00e5ff';
        ctx.shadowBlur = 10;
        ctx.fill();
        ctx.shadowBlur = 0;

        requestAnimationFrame(() => this.animate());
    }
}
