import React from 'react'
import { IndexTable, LegacyCard, ButtonGroup, Page, Button, FormLayout, Checkbox, Card, RadioButton } from '@shopify/polaris';
import { useState, useCallback, useRef, useEffect } from 'react';
import { useToast } from '@shopify/app-bridge-react';
const ShippingOption = () => {
    const { show } = useToast();
    const [checked, setChecked] = useState(false);
    const [shipOptionId, setShipOptionId] = useState(0);
    const handleChange = useCallback((newChecked) => setChecked(newChecked), []);

    const [isChecked, setISChecked] = useState(false);
    const handleISChange = useCallback((newChecked) => setISChecked(newChecked), []);

    const [value, setValue] = useState('ship_address');

    const handleSelectChange = useCallback(
        (_checked, newValue) => setValue(newValue),
        [],
    )
    const [debugChecked, setDebugChecked] = useState(false);
    const handleDebugChange = useCallback((newChecked) => setDebugChecked(newChecked), []);
    
    useEffect(()=> {
        getShippingOption(shipOptionId);
    }, [shipOptionId]);
    

    const getShippingOption = () => {
        fetch("/shipping-options?shop=" + Config.shop).then(res => res.json()).then((result) => {
            if (result.status === 1) {
                setShipOptionId(result.ShippingOption.id)
                setChecked(result.ShippingOption.enable_shipping);
                setISChecked(result.ShippingOption.hide_shipping);
                setValue(result.ShippingOption.ship_destination)
                setDebugChecked(result.ShippingOption.enable_debug);
                
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true });
        })
    }

    function saveShippingOptions ()
    {
        fetch("/shipping-options", {
            method: "POST",
            body: JSON.stringify({
                'optionId': shipOptionId,
                'shop': Config.shop,
                'enable_shipping': checked,
                'hide_shipping': isChecked,
                'ship_destination': value,
                'enable_debug' :debugChecked,
                '_token': Config.csrf_token
            }),
            headers: {
                "Content-type": "application/json"
            }
        }).then(res => res.json()).then((result) => {
            if (result.status) {
                show(result.msg, { duration: 2000 });
                setShowModal(false);
                setShipOptionId(result.option_id);
            } else {
                show(result.msg, { duration: 2000, isError: true });
            }
            setShowSavebar(false);
        }, (error) => {
            show(error, { duration: 2000, isError: true });;
            setShowSavebar(false);
        });
    }
    
    return (
        <div>
            <Page fullWidth
                title="Shipping Options"
                subtitle=""
                className="shipping-zones"
                fullwidth>
                <Card sectioned>
                    <FormLayout>
                        <FormLayout.Group>
                            <label>Shipping Calculator</label>
                            {/* <LegacyCard vertical> */}
                                <Checkbox
                                    label="Enable the shipping calculator on the cart page"
                                    checked={checked}
                                    onChange={handleChange}
                                />
                                {/* <Checkbox
                                    label="Hide shipping costs until an address is entered"
                                    checked={isChecked}
                                    onChange={handleISChange}
                                /> */}
                            {/* </LegacyCard> */}
                        </FormLayout.Group>
                        {/* <FormLayout.Group>
                            <label>Shipping destination</label>
                            <LegacyCard vertical>
                                <RadioButton
                                    label="Default to customer shipping address."
                                    id="ship_address"
                                    name="accounts"
                                    checked={value === 'ship_address'}
                                    onChange={handleSelectChange}
                                />
                                <RadioButton
                                    label="Default to customer billing address."
                                    id="billing_address"
                                    name="accounts"
                                    checked={value === 'billing_address'}
                                    onChange={handleSelectChange}
                                />
                                <RadioButton
                                    label="Force shipping to the customer billing address"
                                    id="force_shipping"
                                    name="accounts"
                                    checked={value === 'force_shipping'}
                                    onChange={handleSelectChange}
                                />
                            </LegacyCard>
                        </FormLayout.Group> */}
                        {/* <FormLayout.Group>
                            <label>Debug mode</label>
                            <LegacyCard vertical>
                                <Checkbox
                                    label="Enable debug mode"
                                    helpText="Enable shipping debug mode to show matching shipping zones and to bypass shipping rate cache."
                                    checked={debugChecked}
                                    onChange={handleDebugChange}
                                />
                            </LegacyCard>
                        </FormLayout.Group> */}
                        <Button primary onClick={saveShippingOptions}>Save Changes</Button>
                    </FormLayout>
                </Card>

            </Page>
        </div>
    )
}

export default ShippingOption
