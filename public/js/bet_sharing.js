/**
 * Bet Sharing Module - Codex SS AAA
 * Permite compartir combinadas con 1-clic vía URL / WhatsApp / Telegram.
 */

const BetSharing = {
    /**
     * Genera un enlace compartible para un conjunto de selecciones del Bet Slip.
     */
    generateShareLink: function (oddIds) {
        if (!oddIds || oddIds.length === 0) {
            alert('Agrega al menos una selección a tu cupón para compartir.');
            return;
        }

        const encodedOdds = btoa(JSON.stringify(oddIds));
        const shareUrl = window.location.origin + '/sportsbook?share_code=' + encodedOdds;

        if (navigator.clipboard) {
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('¡Enlace de apuesta copiado al portapapeles!\n\n' + shareUrl);
            });
        } else {
            prompt('Copia tu enlace de apuesta compartida:', shareUrl);
        }
    },

    /**
     * Revisa la URL al cargar la página para importar selecciones compartidas automáticamente.
     */
    autoImportSharedBet: function () {
        const urlParams = new URLSearchParams(window.location.search);
        const shareCode = urlParams.get('share_code');

        if (shareCode) {
            try {
                const oddIds = JSON.parse(atob(shareCode));
                if (Array.isArray(oddIds) && oddIds.length > 0) {
                    console.log('[BET SHARING] Cargando selecciones compartidas:', oddIds);
                    if (typeof BetSlip !== 'undefined' && BetSlip.importSelections) {
                        BetSlip.importSelections(oddIds);
                    }
                }
            } catch (e) {
                console.error('[BET SHARING] Código de apuesta compartido inválido.', e);
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    BetSharing.autoImportSharedBet();
});
