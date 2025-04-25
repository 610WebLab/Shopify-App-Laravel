import { useEffect } from 'react';
import { useAppBridge } from '@shopify/app-bridge-react';
import { NavigationMenu } from '@shopify/app-bridge/actions';
import { useLocation } from 'react-router-dom';

const NavigationMenuWrapper = () => {
  const app = useAppBridge();
  const location = useLocation();

  useEffect(() => {
    const navigationMenu = NavigationMenu.create(app, {
      items: [
        { label: 'Home', destination: '/' },
        { label: 'Orders', destination: '/pages/orders' },
        { label: 'Other Careers', destination: '/pages/other-careers' },
        { label: 'Label Templates', destination: '/pages/templates' },
        { label: 'Settings', destination: '/pages/settings' },
        { label: 'Plans', destination: '/pages/plan' },
      ],
      active: location.pathname,
    });

    navigationMenu.set({ active: location.pathname });
  }, [location, app]);

  return null; // Does not render anything inside the app UI
};

export default NavigationMenuWrapper;
