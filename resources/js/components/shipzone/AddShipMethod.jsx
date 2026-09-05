import React, { useState, useCallback, useEffect } from 'react';
import {
    Modal,
    VerticalStack,
    ChoiceList,
    Banner,
    HorizontalStack,
    Button,
    ButtonGroup,
} from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';

const METHOD_OPTIONS = [
    {
        value: 'flat_rate',
        label: 'Flat rate',
        helpText: 'Charge a fixed shipping price for orders in this zone.',
    },
    {
        value: 'free_shipping',
        label: 'Free shipping',
        helpText: 'Offer free shipping, optionally based on a minimum order amount.',
    },
    {
        value: 'local_pickup',
        label: 'Local pickup',
        helpText: 'Let customers collect their order from a physical location.',
    },
    {
        value: 'table_rate',
        label: 'Table rates',
        helpText: 'Build flexible rates by weight, price, quantity, or shipping class.',
    },
    {
        value: 'rates_by_distance',
        label: 'Rates by distance',
        helpText: 'Calculate shipping cost based on delivery distance.',
    },
];

const METHOD_URLS = {
    flat_rate: '/v1/flat-rate-shipping',
    free_shipping: '/v1/free-rate-shipping',
    local_pickup: '/v1/local-pickup-shipping',
    table_rate: '/v1/table-rate-shipping',
    rates_by_distance: '/v1/rates_by_distance',
};

const AddShipMethod = ({
    showModal,
    setShowModal,
    zoneId,
    setIsRefreshMethodData,
    setApiResObj,
}) => {
    const { show } = useToast();
    const [selected, setSelected] = useState('flat_rate');
    const [isSaving, setIsSaving] = useState(false);

    const toggleModal = useCallback(() => {
        if (isSaving) {
            return;
        }
        setShowModal((open) => !open);
    }, [isSaving, setShowModal]);

    useEffect(() => {
        if (showModal) {
            setSelected('flat_rate');
            setIsSaving(false);
        }
    }, [showModal]);

    const handleSelectChange = useCallback((value) => {
        setSelected(value[0]);
    }, []);

    const saveShippingMethod = useCallback(() => {
        if (!zoneId) {
            show('Please save the shipping zone first.', { duration: 2000, isError: true });
            return;
        }

        setIsSaving(true);

        fetch(METHOD_URLS[selected], {
            method: 'POST',
            body: JSON.stringify({
                shop: Config.shop,
                shippingMethod: selected,
                zoneId: zoneId,
                status: true,
            }),
            headers: {
                'Content-type': 'application/json',
            },
        })
            .then((res) => res.json())
            .then(
                (result) => {
                    show(result.msg, { duration: 2000, isError: !result.status });
                    setIsRefreshMethodData(true);
                    setApiResObj(result);
                    setIsSaving(false);
                    if (result.status) {
                        setShowModal(false);
                    }
                },
                (error) => {
                    show(error, { duration: 2000, isError: true });
                    setIsSaving(false);
                }
            );
    }, [zoneId, selected, show, setIsRefreshMethodData, setApiResObj, setShowModal]);

    return (
        <Modal open={showModal} onClose={toggleModal} title="Add shipping method">
            <Modal.Section>
                <div className="add-ship-method-modal">
                    <VerticalStack gap="4">
                        <Banner status="info" title="New methods start enabled">
                            <p>
                                Choose a method type for this zone. You can configure pricing and
                                rules after it is added.
                            </p>
                        </Banner>

                        <ChoiceList
                            title="Method type"
                            choices={METHOD_OPTIONS}
                            selected={[selected]}
                            onChange={handleSelectChange}
                        />
                    </VerticalStack>
                </div>
            </Modal.Section>

            <Modal.Section>
                <div className="add-ship-method-modal__actions">
                    <HorizontalStack align="end" gap="2">
                        <ButtonGroup>
                            <Button size="medium" onClick={toggleModal} disabled={isSaving}>
                                Cancel
                            </Button>
                            <Button
                                primary
                                size="medium"
                                onClick={saveShippingMethod}
                                loading={isSaving}
                                disabled={isSaving || !selected}
                            >
                                Add method
                            </Button>
                        </ButtonGroup>
                    </HorizontalStack>
                </div>
            </Modal.Section>
        </Modal>
    );
};

export default AddShipMethod;
