import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Chỉ khởi tạo Echo nếu có cấu hình Reverb
// Tránh lỗi WebSocket khi server không chạy
try {
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    const reverbHost = import.meta.env.VITE_REVERB_HOST;
    
    if (reverbKey && reverbHost) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        // Listen for connection events
        window.Echo.connector.pusher.connection.bind('connected', function() {
            // Echo connected successfully
        });

        window.Echo.connector.pusher.connection.bind('disconnected', function() {
            // Echo disconnected
        });

        window.Echo.connector.pusher.connection.bind('error', function(error) {
            // Silently handle connection errors (server may not be running)
            // console.error('Echo connection error:', error);
        });
    } else {
        // Tạo Echo stub để tránh lỗi khi code khác gọi window.Echo
        window.Echo = {
            channel: () => ({ listen: () => {} }),
            private: () => ({ listen: () => {} }),
        };
    }
} catch (error) {
    // Nếu có lỗi, tạo Echo stub
    window.Echo = {
        channel: () => ({ listen: () => {} }),
        private: () => ({ listen: () => {} }),
    };
}
