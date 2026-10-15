import React from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

import '../css/app.css';
import App from './components/App';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

createRoot(
    document.getElementById('app')
).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
