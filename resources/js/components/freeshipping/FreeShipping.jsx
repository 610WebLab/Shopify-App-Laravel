import React from 'react'
import { VerticalStack, Modal, Card, TextField, Select } from '@shopify/polaris';
import { useState, useCallback, useMemo, useEffect } from 'react';
import { useToast } from '@shopify/app-bridge-react';

const FreeShipping = ({ freeActiveModel, setFreeActiveModel, zoneMethodId, setIsRefreshMethodData, setApiResObj }) => {

    const { show } = useToast();

    const toggleModal = useCallback(() => {
        setFreeActiveModel(false);
    });

    const [title, setTitle] = useState("");
    useEffect(() => {
        if (zoneMethodId) {
            fetchFreeShippingData(zoneMethodId);
        }
    }, [zoneMethodId]);

    const [showFreeShipContent, setShowFreeShipContent] = useState(false);
    const handleTitleChange = useCallback((newValue) => setTitle(newValue), []);
    const [freeShipId, setFreeShipId] = useState(0);
    const [selected, setSelected] = useState(' ');
    const handleSelectChange = useCallback((value) => {
        (value == 'none') ? setShowFreeShipContent(false) : setShowFreeShipContent(true);
        setSelected(value);
    }, []
    );
    const texOptions = [
        { label: 'N/A', value: 'none' },
        { label: 'A Minimum Order Amount', value: 'min_amount' },
        // { label: 'A Valid Free Shipping Coupon', value: 'coupon' },
        // { label: 'A Minimum Order Amount OR A Coupon', value: 'either' },
        // { label: 'A Minimum Order Amount AND A Coupon', value: 'both' },
    ];

    const [cost, setCost] = useState('');
    const handleCostChange = useCallback((newValue) => setCost(newValue), []);

    const [isChecked, setIsChecked] = useState(false);
    const handleIsCheckedChange = useCallback((newChecked) => setIsChecked(newChecked), [])

    const fetchFreeShippingData = async (id) => {
        try {
            const response = await fetch('/v1/free-rate-shipping/' + id);
            const json = await response.json();
            if (json.status) {
                (json.freeShipping.ship_require) ? setShowFreeShipContent(true) : setShowFreeShipContent(false);
                setFreeShipId(json.freeShipping.id);
                setTitle(json.freeShipping.title);
                setSelected(json.freeShipping.ship_require);
                setCost(json.freeShipping.order_amount);
                setIsChecked(json.freeShipping.discount);
                (json.freeShipping.ship_require == "none") ? setShowFreeShipContent(false) : setShowFreeShipContent(true);
            }
        } catch (error) {
            show(error, { duration: 2000, isError: true });
        }
    };

    const saveFreeShipping = () => {
        fetch("/v1/free-rate-shipping/" + freeShipId, {
            method: "POST",
            body: JSON.stringify({
                'shop': Config.shop,
                "_method": "PUT",
                'id': freeShipId,
                "title": title,
                "ship_requir": selected,
                "amount": cost,
                "discount": isChecked,
                '_token': Config.csrf_token

            }),
            headers: {
                "Content-type": "application/json"
            }

        }).then(res => res.json()).then((result) => {
            if (result.status) {
                show(result.msg, { duration: 2000 });
                setIsRefreshMethodData(true);
                setFreeActiveModel(false);
                setApiResObj(result);
            } else {
                setApiResObj(result);
                show(result.msg, { duration: 2000, isError: true })
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true });
        });
    };
    return (
        <div>
            <Modal
                // activator={activator}
                open={freeActiveModel}
                onClose={toggleModal}
                title="Free Shipping Settings"
                primaryAction={{
                    content: 'Save Changes',
                    onAction: saveFreeShipping,
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
                            label="Free Shipping Requires..."
                            options={texOptions}
                            onChange={handleSelectChange}
                            value={selected}
                        />

                        {
                            (showFreeShipContent) ?
                                <>
                                    <TextField
                                        label="Minimum Order Amount"
                                        type="number"
                                        value={cost}
                                        onChange={handleCostChange}
                                        autoComplete="off"
                                    />

                                    {/* <Card  padding="4">
                                        <Checkbox
                                            label="Coupons Discounts"
                                            checked={isChecked}
                                            onChange={handleIsCheckedChange}
                                        />
                                    </Card> */}
                                </>
                                :
                                ""
                        }

                    </VerticalStack>
                </Modal.Section>
            </Modal>
        </div>
    )
}

export default FreeShipping
