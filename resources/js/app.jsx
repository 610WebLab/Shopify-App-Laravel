// import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client'
import '@shopify/polaris/build/esm/styles.css';
import '../css/app.css';
import enTranslations from '@shopify/polaris/locales/en.json';
import { AppProvider } from '@shopify/polaris';
import { Provider, NavigationMenu } from '@shopify/app-bridge-react';
import { BrowserRouter as Router, useLocation, Link } from "react-router-dom";
import App from "./components/App";
// import { Link, useLocation } from 'react-router-dom';



function Navigation() {
    const location = useLocation();
    const path = window.location.href;
    const match = path.match(/\/pages\/[^?#]*/);

    const extractedPath = match ? match[0] : null;
    const navigationLinks = [
        { label: 'Home', destination: '/' },
        { label: 'Orders', destination: '/pages/orders' },
        { label: 'Other Careers', destination: '/pages/other-careers' },
        { label: 'Label Templates', destination: '/pages/templates' },
        { label: 'Settings', destination: '/pages/settings' },
        { label: 'Plans', destination: '/pages/plan' },
    ];
    console.log(extractedPath, "Navigation");
    return (
        <NavigationMenu
            navigationLinks={navigationLinks.map(link => ({
                ...link,
                url: link.destination, // App Bridge NavigationMenu often uses 'url'
            }))}
            matcher={(link) => link.url === location.pathname}
        />
    );
}

createRoot(document.getElementById('root')).render(
    <AppProvider i18n={enTranslations}>
        <Provider config={Config}>

            <NavigationMenu
                navigationLinks={[
                    {
                        label: 'Home',
                        destination: '/',
                    },
                    {
                        label: 'Orders',
                        destination: '/pages/orders',

                    },
                    {
                        label: 'Other Careers',
                        destination: '/pages/other-careers',
                    },
                    {
                        label: 'Label Templates',
                        destination: '/pages/templates',
                    },
                    {
                        label: 'Settings',
                        destination: '/pages/settings',
                    },
                    {
                        label: 'Plans',
                        destination: '/pages/plan',
                    }
                ]}
                matcher={(link, location) => link.destination === location.pathname}
            />
            <Router>
                <App />
            </Router>
        </Provider>
    </AppProvider>
);