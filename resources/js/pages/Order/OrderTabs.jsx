import React, { useState, useEffect, useCallback } from 'react'
import { LegacyCard, Tabs, Page } from '@shopify/polaris';
import CurrentOrder from './CurrentOrder';
import PendingOrder from './PendingOrder';
import FulfilledOrder from './FulfilledOrder';
import OtherOrder from './OtherOrder';

const OrderTabs = () => {
    const [selected, setSelected] = useState(0);
    const handleTabChange = useCallback((selectedTabIndex) => setSelected(selectedTabIndex), []);
    const tabs = [
        {
            id: 'all-customers-4',
            content: 'Today Order',
            element: <CurrentOrder />,
            accessibilityLabel: 'All customers',
            panelID: 'all-customers-content-4',
        },
        {
            id: 'accepts-marketing-4',
            content: 'Unfulfilled Order',
            element: <PendingOrder />,
            panelID: 'accepts-marketing-content-4',
        },
        {
            id: 'repeat-customers-4',
            content: 'Fulfilled Order',
            element: <FulfilledOrder />,
            panelID: 'repeat-customers-content-4',
        },
        {
            id: 'prospects-4',
            content: 'Canceled Order',
            element: <OtherOrder />,
            panelID: 'prospects-content-4',
        },
    ];

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
    )
}

export default OrderTabs
