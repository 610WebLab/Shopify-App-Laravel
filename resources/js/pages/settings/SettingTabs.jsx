import React from 'react'
import { LegacyCard, Tabs, Page } from '@shopify/polaris';
import { useState, useCallback } from 'react';
import DimensionListing from './dimension/DimensionListing';
import StoreLocationsPage from './location/StoreLocationsPage';
import { useLocation } from 'react-router-dom';

export default function SettingTabs() {
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const defaultTab = queryParams.get('tab') || 'store-locations';

  const handleTabChange = useCallback(
    (selectedTabIndex) => setSelected(selectedTabIndex),
    [],
  );

  const tabs = [
    {
      id: 'store-locations',
      content: 'Store Locations',
      element: <StoreLocationsPage />,
      accessibilityLabel: 'Store Locations',
      panelID: 'store-locations-content',
    },
    {
      id: 'dimension',
      content: 'Dimension Setting',
      element: <DimensionListing />,
      accessibilityLabel: 'Dimension Setting',
      panelID: 'Dimension-setting-content-4',
    },
  ];

  const defaultTabIndex = tabs.findIndex((tab) => tab.id === defaultTab);
  const [selected, setSelected] = useState(defaultTabIndex !== -1 ? defaultTabIndex : 0);

  return (
    <Page fullWidth>
      <LegacyCard>
        <Tabs
          tabs={tabs}
          selected={selected}
          onSelect={handleTabChange}
        >
          <LegacyCard.Section>
            {tabs[selected].element}
          </LegacyCard.Section>
        </Tabs>
      </LegacyCard>
    </Page>
  );
}
