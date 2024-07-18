import React from 'react'
import { TextField, FormLayout } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
const AddShippingClass = ({data}) => {
    const [shipClass, setShipClass] = useState('Jaded Pixel');
    const handleShipClassChange = useCallback((newValue) => setShipClass(newValue), []);

    const [shipSlug, setShipSlug] = useState('Jaded Pixel');
    const handleShipSlugChange = useCallback((newValue) => setShipSlug(newValue), []);

    const [shipDecs, setShipDecs] = useState('Jaded Pixel');
    const handleShipDecsChange = useCallback((newValue) => setShipDecs(newValue), []);
    return (
        <div>
            <FormLayout>
                <FormLayout.Group>
                    <TextField
                        label="Shipping class"
                        value={shipClass}
                        onChange={handleShipClassChange}
                        autoComplete="off"
                    />
                    <TextField
                        label="	Slug"
                        value={shipSlug}
                        onChange={handleShipSlugChange}
                        autoComplete="off"
                    /><TextField
                        label="Description"
                        value={shipDecs}
                        onChange={handleShipDecsChange}
                        autoComplete="off"
                    />
                </FormLayout.Group>
            </FormLayout>
        </div>
    )
}

export default AddShippingClass
