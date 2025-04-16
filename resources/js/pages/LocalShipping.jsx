import React from 'react'
import { useState, useCallback, } from 'react';
import { Page, LegacyCard, Tabs, } from '@shopify/polaris';
// import { useLocation, useParams } from "react-router-dom";

import ShippingZone from '../components/ShippingZone';
import ShippingOption from '../components/ShippingOption';

const LocalShipping = () => {

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
  ];

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

export default LocalShipping
