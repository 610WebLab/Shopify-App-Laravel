import React from 'react'
import { Button, Modal, TextContainer, Select, Text, Checkbox, VerticalStack } from '@shopify/polaris';
import { useState, useCallback, useEffect } from 'react';
import { useToast } from '@shopify/app-bridge-react';
const AddShipMethod = ({ showModal, setShowModal, zoneId, setIsRefreshMethodData, setApiResObj }) => {
    const { show } = useToast();

    const toggleModal = useCallback(() => setShowModal((showModal) => !showModal), []);

    const [selected, setSelected] = useState('flat_rate');
    const handleSelectChange = useCallback((value) => setSelected(value), []);
    const optionsMethod = [
        { label: 'Flat Rate', value: 'flat_rate' },
        { label: 'Free Shipping', value: 'free_shipping' },
        { label: 'Local Pickup', value: 'local_pickup' },
        { label: 'Table Rates', value: 'table_rate' },
    ];

    const URLs = {
        'flat_rate': '/v1/flat-rate-shipping',
        'free_shipping': '/v1/free-rate-shipping',
        'local_pickup': '/v1/local-pickup-shipping',
        'table_rate': '/v1/table-rate-shipping',
    };
    const [isChecked, setIsChecked] = useState(false);
    const handleCheckbox = useCallback((value) => setIsChecked(value), []);

    const saveShippingZone = () => {
        if (zoneId.length <= 0) {
            show("Please Enter Zone Name.", { duration: 2000, isError: true })
            return false;
        }

        let data = {
            'shop': Config.shop,
            "shippingMethod": selected,
            "zoneId": zoneId,
            "status": isChecked,
        };

        let ULR = URLs[selected];

        fetch(ULR, {
            method: "POST",
            body: JSON.stringify(data),
            headers: {
                "Content-type": "application/json"
            }

        }).then(res => res.json()).then((result) => {
            show(result.msg, { duration: 2000 })
            setIsRefreshMethodData(true);
            toggleModal();
            setApiResObj(result);
        }, (error) => {
            show(error, { duration: 2000, isError: true });
        });
    };

    return (
        <div>
            <Modal
                // activator={activator}
                open={showModal}
                onClose={toggleModal}
                title="Add shipping method"
                primaryAction={{
                    content: 'Add Shipping Method',
                    onAction: saveShippingZone,
                }}
                secondaryActions={[
                    {
                        content: 'Cancel',
                        onAction: toggleModal,
                    },
                ]}
            >
                <Modal.Section>
                    <VerticalStack gap="4">

                        <TextContainer>
                            <Text variant="headingMd" as="h6">
                                Choose the shipping method you wish to add. Only shipping methods which support zones are listed..
                            </Text>
                        </TextContainer>
                        <Select
                            options={optionsMethod}
                            onChange={handleSelectChange}
                            value={selected}
                        />
                        <Checkbox
                            checked={isChecked}
                            label="Enable Shipping Method"
                            onChange={handleCheckbox}
                        />

                    </VerticalStack>
                </Modal.Section>
            </Modal>

        </div>
    )
}

export default AddShipMethod
