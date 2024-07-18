import React from 'react'
import { VerticalStack, Modal, Text, TextField, Select, Card } from '@shopify/polaris';
import { useState, useCallback, useMemo, useEffect } from 'react';
import { useToast } from '@shopify/app-bridge-react';

const FlatRate = ({ flatActiveModel, setFlatActiveModel, zoneMethodId, setIsRefreshMethodData, setApiResObj }) => {
    const { show } = useToast();
    const toggleModal = useCallback(() => {
        setFlatActiveModel(false);
    });
    const [flatTitle, setFlatTitle] = useState("");
    useEffect(() => {
        if (zoneMethodId) {
            fetchFlatRateData(zoneMethodId);
        }
    }, [zoneMethodId]);

    const handleTitleChange = useCallback((newValue) => setFlatTitle(newValue), []);

    const [flatRateId, setFlatRateId] = useState(0);
    const [zoneId, setZoneId] = useState(0);
    const [flatRateethodId, setFlatRateethodId] = useState(0);
    const [selectedFlat, setSelectedFlat] = useState('taxable');
    const handleSelectChange = useCallback((value) => setSelectedFlat(value), []);
    const texOptions = [
        { label: 'Taxable', value: 'taxable' },
        { label: 'None', value: 'none' },
    ];

    const [flatCost, setFlatCost] = useState('');
    const handleCostChange = useCallback((newValue) => setFlatCost(newValue), []);

    const [shipClassCost, setShipClassCost] = useState('');
    const handleshipClassCostChange = useCallback((newValue) => setShipClassCost(newValue), []);

    const [noShipClassCost, setNoShipClassCost] = useState('');
    const handleNoShipClassCostChange = useCallback((newValue) => setNoShipClassCost(newValue), []);

    const [calculationType, setCalculationType] = useState('order');
    const handleCalculationTypeChange = useCallback((value) => setCalculationType(value), []);
    const calculationOptions = [
        { label: 'Per Order: Charge shipping for the most expensive shipping class', value: 'order' },
        { label: 'Per Class: Charge hipping for each shipping class individually', value: 'class' },

    ];
    console.log("class", calculationType)
    const fetchFlatRateData = async (id) => {
        try {
            const response = await fetch('/v1/flat-rate-shipping/' + id);
            const json = await response.json();
            if (json.status) {
                setFlatRateId(json.flatRate.id);
                setZoneId(json.flatRate.zone_id);
                setFlatRateethodId(json.flatRate.shipping_method_id);
                setFlatTitle(json.flatRate.title);
                setSelectedFlat(json.flatRate.tax_status);
                setFlatCost(json.flatRate.flat_rate_cost);
                setShipClassCost(json.flatRate.ship_class_cost);
                setNoShipClassCost(json.flatRate.no_ship_class_cost);
                setCalculationType(json.flatRate.calculation_type);
            }
        } catch (error) {
            show(error, { duration: 2000, isError: true });
        }
    };

    const saveFlatRate = () => {
        fetch("/v1/flat-rate-shipping/" + flatRateId, {
            method: "POST",
            body: JSON.stringify({
                'shop': Config.shop,
                "_method": "PUT",
                'id': flatRateId,
                "title": flatTitle,
                "taxstatus": selectedFlat,
                "cost": flatCost,
                'ship': shipClassCost,
                'noship': noShipClassCost,
                'calculation': calculationType,
                '_token': Config.csrf_token
            }),
            headers: {
                "Content-type": "application/json"
            }

        }).then(res => res.json()).then((result) => {
            if (result.status) {
                show(result.msg, { duration: 2000 });
                setIsRefreshMethodData(true);
                setFlatActiveModel(false);
                setApiResObj(result);
            } else {
                setApiResObj(result);
                show(result.msg, { duration: 2000, isError: true });
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true });
        });

    };

    return (
        <div>
            <Modal
                // activator={activator}
                open={flatActiveModel}
                onClose={toggleModal}
                title="Flat Rate Settings"
                primaryAction={{
                    content: 'Save Changes',
                    onAction: saveFlatRate,
                }}
            >
                <Modal.Section>
                    <VerticalStack gap="4">
                        {/* <Card padding="4"> */}
                            <TextField
                                label="Title"
                                value={flatTitle}
                                onChange={handleTitleChange}
                                autoComplete="off"
                            />
                        {/* </Card> */}
                        {/* <Card padding="4"> */}
                            <Select
                                label="Tax status"
                                options={texOptions}
                                onChange={handleSelectChange}
                                value={selectedFlat}
                            />
                        {/* </Card> */}
                        {/* <Card padding="4"> */}
                            <TextField
                                label="Cost"
                                type="number"
                                value={flatCost}
                                onChange={handleCostChange}
                                autoComplete="off"
                            />
                        {/* </Card> */}
                        {/* <Card>
                            <Text variant="headingXl" as="h4">Shipping Class Costs</Text>
                            <p>These costs can optionally be added based on the product shipping class.</p>
                        </Card> */}
                        {/* <Card padding="4">
                            <TextField
                                label="shipping Class Cost"
                                type="number"
                                value={shipClassCost}
                                onChange={handleshipClassCostChange}
                                autoComplete="off"
                            />
                        </Card> */}
                        {/* <Card padding="4">
                            <TextField
                                label="No Shipping Class Cost"
                                type="number"
                                value={noShipClassCost}
                                onChange={handleNoShipClassCostChange}
                                autoComplete="off"
                            />
                        </Card>
                        <Card padding="4">
                            <Select
                                label="Calculation Type"
                                options={calculationOptions}
                                onChange={handleCalculationTypeChange}
                                value={calculationType}
                            />
                        </Card> */}

                    </VerticalStack>
                </Modal.Section>
            </Modal>
        </div>
    )
}

export default FlatRate
