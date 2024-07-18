import React from 'react'
import { FormLayout, TextField, Link, Tooltip, Checkbox, ButtonGroup, IndexTable, useIndexResourceState, DataTable, Button, LegacyCard } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
const AddShippingClass = ({ shipClass }) => {
    const [shipClassName, setShipClassName] = useState('');
    const handleShipClassChange = useCallback((newValue) => setShipClassName(newValue), []);

    const [shipSlug, setShipSlug] = useState('');
    const handleShipSlugChange = useCallback((newValue) => setShipSlug(newValue), []);

    const [shipDecs, setShipDecs] = useState('');
    const handleShipDecsChange = useCallback((newValue) => setShipDecs(newValue), []);

    const rows = shipClass.map(
        (ship, index) => (
            <IndexTable.Row
                id={index}
                key={"shipcls1-" + index}
                position={index}
            >
                <IndexTable.Cell key={"shiprate2-" + index}>
                    <TextField value={shipClassName} onChange={handleShipClassChange} placeholder="Shipping Class Name" autoComplete="off" />
                    <Link url="#" index={index}>Cancel Changes</Link>
                </IndexTable.Cell>
                <IndexTable.Cell key={"shiprate3-" + index}>
                    <TextField value={shipSlug} onChange={handleShipSlugChange} placeholder="Slug" autoComplete="off" />
                </IndexTable.Cell>
                <IndexTable.Cell key={"shiprate4-" + index}>
                    <TextField value={shipDecs} onChange={handleShipDecsChange} placeholder="Description For Your Reference" autoComplete="off" />
                </IndexTable.Cell>
                <IndexTable.Cell key={"shiprate5-" + index}>
                </IndexTable.Cell>
            </IndexTable.Row>
        ),
    );
    return (rows)
}

export default AddShippingClass
