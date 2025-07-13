import { createApp } from 'vue';
import App from './App.vue';
import { setupI18n } from './i18n';

async function initializeApp() {
    try {
        const app = createApp(App);
        const i18n = await setupI18n();
        
        app.use(i18n);
        app.mount('#app');
        
        console.log('Application initialized successfully');
    } catch (error) {
        console.error('Failed to initialize app:', error);
    }
}

// تأكد من تحميل DOM أولاً
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});