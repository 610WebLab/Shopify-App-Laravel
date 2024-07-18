// import './bootstrap';
import React from 'react';
import { createRoot  } from 'react-dom/client'
import '@shopify/polaris/build/esm/styles.css';
import '../css/app.css';
import enTranslations from '@shopify/polaris/locales/en.json';
import { AppProvider } from '@shopify/polaris';
import { Provider } from '@shopify/app-bridge-react';
import { BrowserRouter as Router } from "react-router-dom";
import App from "./components/App";

createRoot(document.getElementById('root')).render(
      <AppProvider i18n={enTranslations}>
            <Provider config={Config}>
                  <Router>
                        <App />
                  </Router>
            </Provider>
      </AppProvider>
);