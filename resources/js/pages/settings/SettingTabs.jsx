import React from 'react'
import { LegacyCard, Tabs, Page } from '@shopify/polaris';
import { useState, useCallback } from 'react';
// import TemplateListPage from './templates/TemplateListPage'
import DimensionListing from './dimension/DimensionListing';
import { useLocation, useNavigate } from 'react-router-dom';
export default function SettingTabs() {
  const location = useLocation();
  const navigate = useNavigate();
  const queryParams = new URLSearchParams(location.search);
  const defaultTab = queryParams.get('tab') || 'label-setting';
  // const [selected, setSelected] = useState(0);

  const handleTabChange = useCallback(
    (selectedTabIndex) => setSelected(selectedTabIndex),
    [],
  );

  const tabs = [
    // {
    //   id: 'lable-setting',
    //   content: "Lable Setting",
    //   element: <TemplateListPage />,
    //   accessibilityLabel: 'Lable Setting',
    //   panelID: 'lable-setting-content-4',
    // },
    {
      id: 'dimension',
      content: "Dimension Setting",
      element: <DimensionListing />,
      accessibilityLabel: 'Dimension Setting',
      panelID: 'Dimension-setting-content-4',
    }
  ];
  const defaultTabIndex = tabs.findIndex(tab => tab.id === defaultTab);
  const [selected, setSelected] = useState(defaultTabIndex !== -1 ? defaultTabIndex : 0);

  console.log(location.pathname, "Settings tabs")
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
