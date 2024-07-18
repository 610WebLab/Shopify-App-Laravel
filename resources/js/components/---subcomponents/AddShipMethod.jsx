import React from 'react'
import { Button, Modal, TextContainer, Select, Stack, Checkbox } from '@shopify/polaris';
import { useState, useCallback, useEffect } from 'react';

const AddShipMethod = ({ activeModal }) => {
    // const [showModal, setShowModal] = useState(activeModal);
    // const toggleModal = useCallback(() => setShowModal((showModal) => !showModal), []);

    const [active, setActive] = useState({activeModal});
    const handleChange = useCallback(() => setActive(!active), [active]);
    useEffect(() => {
        setActive(activeModal);

    }, [activeModal]);
    // const activator = <Button onClick={handleChange}>Open</Button>;
    console.log("ActiveModal", active)

    const [selected, setSelected] = useState('flat_rate');
    const handleSelectChange = useCallback((value) => setSelected(value), []);
    const options = [
        { label: 'Flat Rate', value: 'flat_rate' },
        { label: 'Free Shipping', value: 'free_shipping' },
        { label: 'Local Pickup', value: 'local_pickup' },
        { label: 'Table Rates', value: 'table_rate' },
    ];

    const [isChecked, setIsChecked] = useState(false);
    const handleCheckbox = useCallback((value) => setIsChecked(value), []);
    return (
        <div style={{ height: '500px' }}>
            <Modal
                // activator={activator}
                open={active}
                onClose={handleChange}
                title="Add shipping method"
                primaryAction={{
                    content: 'Add Shipping Method',
                    onAction: console.log("rows"),
                }}
                secondaryActions={[
                    {
                        content: 'Cancel',
                        onAction: handleChange,
                    },
                ]}
            >
                <Modal.Section>
                    <Stack vertical>
                        <Stack.Item>
                            <TextContainer>
                                <p>
                                    Choose the shipping method you wish to add. Only shipping methods which support zones are listed..
                                </p>
                            </TextContainer>
                        </Stack.Item>
                        <Stack.Item fill>
                            <Select
                                options={options}
                                onChange={handleSelectChange}
                                value={selected}
                            />
                        </Stack.Item>
                        <Stack.Item>
                            <Checkbox
                                checked={isChecked}
                                label="Enable Shipping Method"
                                onChange={handleCheckbox}
                            />
                        </Stack.Item>
                    </Stack>
                </Modal.Section>

            </Modal>
        </div>
    )
}

export default AddShipMethod
