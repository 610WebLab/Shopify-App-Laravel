import React from 'react'
import { useState, useCallback, useRef, useEffect } from 'react';
import { Page, Grid, Card, List, Tabs, LegacyCard } from '@shopify/polaris';
import { useLocation, Link, useNavigate, useParams } from "react-router-dom";
import { HomeMajor, AnalyticsMinor, TemplateMinor, BehaviorMinor } from '@shopify/polaris-icons';
import AddTableRates from './AddTableRates';
import ShippingRates from './ShippingRates';

const TableRateTabs = () => {
    const { TableRateID } = useParams();
    const [selected, setSelected] = useState(0);
    const handleTabChange = useCallback(
        (selectedTabIndex) => setSelected(selectedTabIndex),
        [],
    );
    const[changeCalculationType, setChangeCalculationType] = useState("per_order");
    const tabs = [
        {
            id: 'tableRates-1',
            content: "Table Rate",
            element: <AddTableRates TableRateID={TableRateID} setChangeCalculationType={setChangeCalculationType}/>,
            accessibilityLabel: 'Shipping Zones',
            panelID: 'Shipping-Zone-1',

        },
        {
            id: 'tablRatesOption-1',
            content: "Table Rate Options",
            element: <ShippingRates  TableRateID={TableRateID} changeCalculationType={changeCalculationType}/>,
            panelID: 'Analytics-content-1',
        }
    ];
    return (
        <Page fullWidth>
            <Card>
                <Tabs tabs={tabs} selected={selected} onSelect={handleTabChange}>
                    <LegacyCard.Section>
                        {tabs[selected].element}
                    </LegacyCard.Section>
                </Tabs>
            </Card>
        </Page>
    )
}

export default TableRateTabs
