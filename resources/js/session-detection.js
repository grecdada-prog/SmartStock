/**
 * Session Detection - Détecte la fermeture du navigateur ou l'interruption de session
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'utilisateur est connecté
    const isAuthenticated = document.querySelector('meta[name="authenticated"]');
    
    if (!isAuthenticated) {
        return;
    }

    // Flag pour savoir si on a fait une déconnexion volontaire
    let isLogoutIntentional = false;

    // Intercepter les clics sur les boutons de déconnexion
    const logoutButtons = document.querySelectorAll('[data-logout]');
    logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            isLogoutIntentional = true;
        });
    });

    // Détecter les changements de page/fermeture d'onglet
    window.addEventListener('beforeunload', function(e) {
        if (!isLogoutIntentional) {
            // Envoyer une requête pour fermer la session
            navigator.sendBeacon('/api/close-session', JSON.stringify({
                user_id: document.querySelector('meta[name="user-id"]')?.content,
            }));
        }
    });

    // Détecter l'inactivité et afficher une alerte
    let inactivityTimer;
    const inactivityTimeout = 25 * 60 * 1000; // 25 minutes (avant les 30 minutes du serveur)

    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        
        inactivityTimer = setTimeout(function() {
            // Afficher une notification avant la déconnexion
            showInactivityWarning();
        }, inactivityTimeout);
    }

    // Réinitialiser le timer sur chaque activité
    document.addEventListener('mousemove', resetInactivityTimer);
    document.addEventListener('keypress', resetInactivityTimer);
    document.addEventListener('click', resetInactivityTimer);
    document.addEventListener('scroll', resetInactivityTimer);
    document.addEventListener('touchstart', resetInactivityTimer);

    // Démarrer le timer
    resetInactivityTimer();
});

/**
 * Afficher un avertissement d'inactivité
 */
function showInactivityWarning() {
    const warning = document.createElement('div');
    warning.className = 'fixed bottom-4 right-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded shadow-lg z-50';
    warning.innerHTML = `
        <div class="font-bold mb-2">⚠️ Inactivité détectée</div>
        <p class="text-sm mb-4">Vous serez déconnecté dans 5 minutes par inactivité.</p>
        <div class="space-x-2">
            <button onclick="this.parentElement.parentElement.remove(); location.reload();" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm">
                Rester connecté
            </button>
            <button onclick="logoutUser();" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                Déconnecter
            </button>
        </div>
    `;
    document.body.appendChild(warning);

    // Auto-remove après 5 minutes
    setTimeout(() => {
        if (warning.parentElement) {
            warning.remove();
        }
    }, 5 * 60 * 1000);
}

/**
 * Déconnecter l'utilisateur
 */
function logoutUser() {
    // Créer un formulaire de déconnexion
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    // Ajouter le token CSRF
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = token.content;
        form.appendChild(input);
    }

    // Soumettre le formulaire
    document.body.appendChild(form);
    form.submit();
}