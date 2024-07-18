import React from 'react'
import { useState, useCallback, useRef, useEffect } from 'react';
import { Page, Grid, LegacyCard, List, Tabs, Icon } from '@shopify/polaris';
import { HomeMajor, AnalyticsMinor, TemplateMinor, BehaviorMinor } from '@shopify/polaris-icons';
import { useLocation, useParams } from "react-router-dom";
import ShippingZone from '../components/ShippingZone';
import ShippingOption from '../components/ShippingOption';
import MenuRoutes from './MenuRoutes';

const AppFrame = () => {
  const location = useLocation();
  const [selected, setSelected] = useState(0);

  const handleTabChange = useCallback(
    (selectedTabIndex) => setSelected(selectedTabIndex),
    [],
  );
  const tabs = [
    {
      id: 'shipZone-1',

      content: "Shipping Zones",
      element: <ShippingZone />,
      accessibilityLabel: 'Shipping Zones',
      panelID: 'Shipping-Zone-1',

    },
    {
      id: 'shipOption-1',
      content: "Shipping Options",
      element: <ShippingOption />,
      panelID: 'Analytics-content-1',
    }
    // {
    //   id: 'shipClass-1',
    //   content: "Shipping Classes",
    //   element: <ShippingClass/>,
    //   panelID: 'Templates-content-1',
    // }
  ];
  console.log(tabs, selected, "tabSelected")
  if (location.pathname.includes('rate')) {
    return (<MenuRoutes />);
  }
  if (location.pathname.includes('new')) {
    return (<MenuRoutes />);
  } else {
    return (
      <Page fullWidth>
        <LegacyCard>
          <Tabs tabs={tabs} selected={selected} onSelect={handleTabChange}>
            <LegacyCard.Section>
              {tabs[selected].element}
            </LegacyCard.Section>
          </Tabs>
        </LegacyCard>
      </Page>
    );
  }
}

export default AppFrame
