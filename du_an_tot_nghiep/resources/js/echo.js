import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Echo initialized

// Listen for connection events
window.Echo.connector.pusher.connection.bind('connected', function() {
    // Echo connected successfully
});

window.Echo.connector.pusher.connection.bind('disconnected', function() {
    // Echo disconnected
});

window.Echo.connector.pusher.connection.bind('error', function(error) {
    console.error('Echo connection error:', error);
});
