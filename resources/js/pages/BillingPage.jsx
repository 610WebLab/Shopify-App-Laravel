import React, { useState, useEffect } from 'react';
import {
    Page, Button, Grid, LegacyCard, Modal, Text, Spinner
} from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';
import { useNavigate } from "react-router-dom";

export default function BillingPage() {
    const { show } = useToast();
    const navigate = useNavigate();

    const [plans, setPlans] = useState([]);
    const [activePlan, setActivePlan] = useState(null);
    const [loader, setLoader] = useState(false);
    const [modalActive, setModalActive] = useState(false);
    const [selectedPlanId, setSelectedPlanId] = useState(null);

    const getShopifyPlans = async () => {
        try {
            const response = await fetch("/get_plan?shop=" + Config.shop);
            const result = await response.json();
            if (result.status) {
                setPlans(result.plans);
                setActivePlan(result.activePlan);
            } else {
                console.error('Failed to fetch billing plans');
            }
        } catch (error) {
            console.error('Error fetching billing plans:', error);
        }
    };

    useEffect(() => {
        getShopifyPlans();
    }, []);

    const handleConfirmAction = async () => {
        setLoader(true);
        fetch('/update_free_plan/1', {
            method: "POST",
            body: JSON.stringify({
                'shop': Config.shop,
                '_token': Config.csrf_token,
            }),
            headers: {
                "Content-type": "application/json"
            }
        })
            .then(res => res.json())
            .then((result) => {
                setLoader(false);
                setModalActive(false);  // Close modal
                if (result.status) {
                    getShopifyPlans();
                    show("Successfully switched to the Free Plan!", { duration: 2000 });
                } else {
                    show(result.message, { duration: 2000, isError: true });
                }
            });
    };

    const handleFreePlan = (planId) => {
        if (planId !== 1) {
            show("Invalid plan selection.", { duration: 2000, isError: true });
            return;
        }
        setSelectedPlanId(planId);
        setModalActive(true);
    };

    return (
        <div className='billing-page'>
            <Page title="Plans" fullWidth>
                {loader ? <Spinner accessibilityLabel="Loading plans" size="large" /> :
                    <Grid>
                        {plans.length > 0 && plans.map((plan, index) => (
                            <Grid.Cell key={index} columnSpan={{ xs: 4, sm: 3, md: 3, lg: 4, xl: 4 }}>
                                <LegacyCard title={plan.name} sectioned>
                                    {(plan.id === 1) && <>
                                        <div className='table-pricing'>
                                            <div className='pricing-text'>
                                                <span></span>
                                                <h2 className='price-text free-to-install'>Free</h2>
                                                <span></span>
                                            </div>
                                        </div>
                                        <ul>
                                            <li>Up to 50 labels/month</li>
                                            <li>Distance & Location Based Rates</li>
                                            <li>Carrier-Based Rates: UPS, USPS, and more</li>
                                            <li>Design templates for custom rates</li>
                                        </ul>
                                    </>}

                                    {(plan.id > 1) && <>
                                        <div className='table-pricing'>
                                            <div className='pricing-text'>
                                                <span>$</span>
                                                <h2 className='price-text'>{plan.price}</h2>
                                                <span>/per month</span>
                                            </div>
                                        </div>
                                        <ul>
                                            {(plan.id == 2) ?
                                                <>
                                                    <li>Up to 100 labels/month</li>
                                                    <li>Distance & Location Based Rates</li>
                                                    <li>Flexible rates for various locations</li>
                                                    <li>EasyPost, UPS, USPS & more carrier rates</li>
                                                    <li>Design templates for custom rates</li>
                                                    <li>Unlimited custom shipping rates</li>

                                                </> :
                                                <>
                                                    <li>Unlimited labels/month</li>
                                                    <li>Distance & Location Based Rates</li>
                                                    <li>Flexible rates for various locations</li>
                                                    <li>EasyPost, UPS, USPS & more carrier rates</li>
                                                    <li>Design templates for custom rates</li>
                                                    <li>Unlimited custom shipping rates</li>
                                                </>

                                            }
                                        </ul>
                                    </>}

                                    {plan.id === 1 ? (
                                        <Button disabled={activePlan === plan.id} onClick={() => handleFreePlan(plan.id)}>
                                            Select Plan
                                        </Button>
                                    ) : (
                                        <Button disabled={activePlan === plan.id}>
                                            <a
                                                href={`/billing/${plan.id}?shop=${Config.shop}&host=${Config.host}`}
                                                style={{ color: 'inherit', textDecoration: 'none' }}
                                            >
                                                Select Plan
                                            </a>
                                        </Button>
                                    )}
                                </LegacyCard>
                            </Grid.Cell>
                        ))}
                    </Grid>
                }
            </Page>

            {/* Polaris Modal */}
            <Modal
                size="small"
                open={modalActive}
                onClose={() => setModalActive(false)}
                title="Confirm Plan Switch"
                primaryAction={{
                    content: loader ? "Processing..." : "Confirm",
                    onAction: handleConfirmAction,
                    disabled: loader
                }}
                secondaryActions={[{ content: "Cancel", onAction: () => setModalActive(false) }]}
            >

                <Modal.Section>
                    <Text>
                        Are you sure you want to switch to the Free Plan?
                    </Text>
                </Modal.Section>

            </Modal>
        </div>
    );
}
