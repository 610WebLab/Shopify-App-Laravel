import React from 'react'
import { VerticalStack, Modal, Card, TextField, Select } from '@shopify/polaris';
import { useState, useCallback, useMemo, useEffect } from 'react';
import { useToast } from '@shopify/app-bridge-react';

const LocalPickup = ({ localActiveModel, setLocalActiveModel, zoneMethodId, setIsRefreshMethodData, setApiResObj }) => {
    const { show } = useToast();
    // const [active, setActive] = useState(localActiveModel);

    const toggleModal = useCallback(() => {
        setLocalActiveModel(false);
    });

    useEffect(() => {
        if (zoneMethodId) {
            fetchLocalPickUpData(zoneMethodId);
        }
    }, [zoneMethodId]);

    const [title, setTitle] = useState("");
    const handleTitleChange = useCallback((newValue) => setTitle(newValue), []);
    const [selected, setSelected] = useState('taxable');
    const handleSelectChange = useCallback((value) => setSelected(value), []);
    const texOptions = [
        { label: 'Taxable', value: 'taxable' },
        { label: 'None', value: 'none' },
    ];

    const [cost, setCost] = useState('');
    const handleCostChange = useCallback((newValue) => setCost(newValue), []);

    const [localPickupId, setLocalPickupId] = useState(0);
    const fetchLocalPickUpData = async (id) => {
        try {
            const response = await fetch('/v1/local-pickup-shipping/' + id);
            const json = await response.json();
            if (json.status) {
                setLocalPickupId(json.localPickUp.id);
                setTitle(json.localPickUp.title);
                setSelected(json.localPickUp.tax_status);
                setCost(json.localPickUp.pickup_cost);
            }
        } catch (error) {
            show(error, { duration: 2000, isError: true });
        }
    };

    const saveLocalPickup = () => {
        fetch("/v1/local-pickup-shipping/" + localPickupId, {
            method: "POST",
            body: JSON.stringify({
                'shop': Config.shop,
                "_method": "PUT",
                'id': localPickupId,
                "title": title,
                "taxstatus": selected,
                "cost": cost,
                '_token': Config.csrf_token
            }),
            headers: {
                "Content-type": "application/json"
            }

        }).then(res => res.json()).then((result) => {

            if (result.status) {
                show(result.msg, { duration: 2000 })
                setIsRefreshMethodData(true);
                setLocalActiveModel(false);
                setApiResObj(result);
            } else {
                setApiResObj(result);
                show(result.msg, { duration: 2000, isError: true })
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true })
        });

    };

    return (
        <div>
            <Modal
                // activator={activator}
                open={localActiveModel}
                onClose={toggleModal}
                title="Local pickup Settings"
                primaryAction={{
                    content: 'Save Changes',
                    onAction: saveLocalPickup,
                }}
            >
                <Modal.Section>
                    <VerticalStack gap="4">

                        <TextField
                            label="Title"
                            value={title}
                            onChange={handleTitleChange}
                            autoComplete="off"
                        />
                        <Select
                            label="Tax status"
                            options={texOptions}
                            onChange={handleSelectChange}
                            value={selected}
                        />
                        <TextField
                            label="Cost"
                            type="number"
                            value={cost}
                            onChange={handleCostChange}
                            autoComplete="off"
                        />

                    </VerticalStack>
                </Modal.Section>
            </Modal>
        </div>
    )
}

export default LocalPickup
