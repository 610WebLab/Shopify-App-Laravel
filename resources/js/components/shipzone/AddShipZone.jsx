import React, { useState, useCallback, useMemo, useEffect } from 'react';
import {
    FormLayout,
    TextField,
    Loading,
    Text,
    Page,
    Button,
    IndexTable,
    Modal,
    TextContainer,
    Icon,
    Layout,
    Card,
    Badge,
    Box,
    Spinner,
    EmptyState,
    VerticalStack,
    HorizontalStack,
    ButtonGroup,
} from '@shopify/polaris';
import { DeleteMinor } from '@shopify/polaris-icons';
import { useNavigate, useParams } from 'react-router-dom';
import { useToast } from '@shopify/app-bridge-react';
import axios from 'axios';
import Select from 'react-select';
import LocalPickup from '../localpickup/LocalPickup';
import FlatRate from '../flatrate/FlatRate';
import FreeShipping from '../freeshipping/FreeShipping';
import AddShipMethod from './AddShipMethod';
import CheckboxMethodStatus from './CheckboxMethodStatus';

const METHOD_URLS = {
    flat_rate: '/v1/flat-rate-shipping',
    free_shipping: '/v1/free-rate-shipping',
    local_pickup: '/v1/local-pickup-shipping',
    table_rate: '/v1/table-rate-shipping',
    rates_by_distance: '/v1/rates_by_distance',
};

const resourceName = {
    singular: 'shipping method',
    plural: 'shipping methods',
};

const toTitleCase = (str) =>
    str
        .toLowerCase()
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const AddShipZone = () => {
    const { show } = useToast();
    const params = useParams();
    const navigate = useNavigate();
    const zoneID = params.ZoneID === undefined ? 0 : params.ZoneID;

    const [isLoaded, setIsLoaded] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [zoneId, setZoneId] = useState(zoneID);
    const [zoneName, setZoneName] = useState('');
    const [isZoneChecked, setIsZoneChecked] = useState(true);
    const [postCode, setPostCode] = useState('');
    const [selectedCountry, setSelectedCountry] = useState([]);
    const [selectedOptions, setSelectedOptions] = useState([]);
    const [selectedZoneOptions, setSelectedZoneOptions] = useState([]);
    const [shippingMethods, setShippingMethods] = useState([]);
    const [errors, setErrors] = useState({ zoneName: '', regions: '' });
    const [apiResObj, setApiResObj] = useState({ status: false, msg: '' });

    const [isRefreshMethodData, setIsRefreshMethodData] = useState(false);
    const [localActiveModel, setLocalActiveModel] = useState(false);
    const [flatActiveModel, setFlatActiveModel] = useState(false);
    const [freeActiveModel, setFreeActiveModel] = useState(false);
    const [zoneMethodId, setZoneMethodId] = useState(0);
    const [zoneFlatRateMethodId, setZoneFlatRateMethodId] = useState(0);
    const [zoneFreeMethodId, setZoneFreeMethodId] = useState(0);
    const [zoneLocalPickupMethodId, setZoneLocalPickupMethodId] = useState(0);
    const [delMethodName, setDelMethodName] = useState('');
    const [delActiveConfirm, setDelActiveConfirm] = useState(false);
    const [showModal, setShowModal] = useState(false);

    const optionList = useMemo(
        () =>
            selectedCountry.map((country) => ({
                value: country.state_name
                    ? `${country.country_code}:${country.state_code}`
                    : country.country_code,
                label: country.state_name
                    ? `${country.state_name}, ${country.country_name}`
                    : country.country_name,
            })),
        [selectedCountry]
    );

    const navigateHome = useCallback(() => {
        navigate('/');
    }, [navigate]);

    const navigateTableRate = useCallback(
        (id, shipMethod) => {
            if (shipMethod === 'table_rate') {
                navigate('/rate/' + id);
            } else {
                navigate('/distance/' + id);
            }
        },
        [navigate]
    );

    const handleZoneNameChange = useCallback((newValue) => {
        setZoneName(newValue);
        if (newValue.trim()) {
            setErrors((prev) => ({ ...prev, zoneName: '' }));
        }
    }, []);

    const handlePostCodeChange = useCallback((newValue) => {
        setPostCode(newValue);
    }, []);

    const handleSelect = useCallback((data) => {
        const regions = (data || []).map((country) => country.value);
        setSelectedZoneOptions(regions);
        setSelectedOptions(data || []);
        if (data && data.length > 0) {
            setErrors((prev) => ({ ...prev, regions: '' }));
        }
    }, []);

    const openDeleteConfirmation = useCallback((id, method) => {
        if (!id) {
            return;
        }
        setZoneMethodId(id);
        setDelMethodName(method);
        setDelActiveConfirm(true);
    }, []);

    const closeDeleteConfirmation = useCallback(() => {
        setDelActiveConfirm(false);
        setZoneMethodId(0);
        setDelMethodName('');
    }, []);

    const editShippingMethod = useCallback(
        (id, shipMethod) => {
            if (shipMethod === 'local_pickup') {
                setLocalActiveModel(true);
                setZoneLocalPickupMethodId(id);
                setIsRefreshMethodData(false);
                return;
            }
            if (shipMethod === 'flat_rate') {
                setFlatActiveModel(true);
                setZoneFlatRateMethodId(id);
                setIsRefreshMethodData(false);
                return;
            }
            if (shipMethod === 'free_shipping') {
                setFreeActiveModel(true);
                setZoneFreeMethodId(id);
                setIsRefreshMethodData(false);
                return;
            }
            setLocalActiveModel(false);
            setFlatActiveModel(false);
            setFreeActiveModel(false);
            navigateTableRate(id, shipMethod);
        },
        [navigateTableRate]
    );

    const getCountries = () => {
        fetch('/countries?shop=' + Config.shop)
            .then((res) => res.json())
            .then(
                (result) => {
                    if (result.status === 1) {
                        setSelectedCountry(result.country);
                    }
                },
                (error) => {
                    show(error, { duration: 2000, isError: true });
                }
            );
    };

    const getShipZone = (id) => {
        setIsLoaded(false);
        fetch('/shipzone/' + id + '?shop=' + Config.shop)
            .then((res) => res.json())
            .then(
                (result) => {
                    if (result.status === 1) {
                        if (result.zone_exist) {
                            setZoneName(result.zone_exist.ship_zone || '');
                            setIsZoneChecked(!!result.zone_exist.status);
                            const regions = result.zone_exist.zone_region || [];
                            setSelectedOptions(regions);
                            setSelectedZoneOptions(
                                regions.map((region) => region.value).filter(Boolean)
                            );
                            setPostCode(result.zone_exist.zip || '');
                        }
                        if (result.zone_mthd) {
                            setShippingMethods(result.zone_mthd);
                        } else {
                            setShippingMethods([]);
                        }
                    }
                    setIsLoaded(true);
                },
                (error) => {
                    show(error, { duration: 2000, isError: true });
                    setIsLoaded(true);
                }
            );
    };

    const delShippingMethod = () => {
        const id = zoneMethodId !== 0 ? zoneMethodId : 0;
        const URL = METHOD_URLS[delMethodName] + '/' + zoneMethodId;
        if (!id) {
            return;
        }
        axios
            .delete(URL + '?shop=' + Config.shop)
            .then((res) => {
                if (res.status == 200) {
                    closeDeleteConfirmation();
                    getShipZone(zoneId);
                    show(res.data.msg, { duration: 2000 });
                } else {
                    show(res.data.msg, { duration: 2000, isError: true });
                }
            });
    };

    const validateForm = useCallback(() => {
        const nextErrors = { zoneName: '', regions: '' };
        let valid = true;

        if (!zoneName || zoneName.trim().length <= 0) {
            nextErrors.zoneName = 'Zone name is required';
            valid = false;
        }

        if (!selectedOptions || selectedOptions.length === 0) {
            nextErrors.regions = 'Select at least one region so rates apply to the right customers.';
        }

        setErrors(nextErrors);
        return valid;
    }, [zoneName, selectedOptions]);

    const saveAction = useCallback(() => {
        if (!validateForm()) {
            show('Please enter a zone name.', { duration: 2000, isError: true });
            return;
        }

        setIsSaving(true);
        fetch('/shipzone', {
            method: 'POST',
            body: JSON.stringify({
                zoneId: zoneId,
                shop: Config.shop,
                shipzone: zoneName,
                status: isZoneChecked,
                region: selectedOptions,
                data: selectedZoneOptions,
                postcode: postCode,
                _token: Config.csrf_token,
            }),
            headers: {
                'Content-type': 'application/json',
            },
        })
            .then((res) => res.json())
            .then((result) => {
                if (result.status === 1) {
                    show(result.msg, { duration: 2000 });
                    setZoneId(result.zond_id);
                    setShowModal(false);
                } else {
                    show(result.msg, { duration: 5000, isError: true });
                }
                setIsSaving(false);
            })
            .catch(() => {
                show('Unable to save shipping zone.', { duration: 3000, isError: true });
                setIsSaving(false);
            });
    }, [
        validateForm,
        zoneName,
        selectedOptions,
        zoneId,
        isZoneChecked,
        selectedZoneOptions,
        postCode,
        show,
    ]);

    const openShipMethod = useCallback(() => {
        setShowModal(true);
        setIsRefreshMethodData(false);
    }, []);

    useEffect(() => {
        getCountries();
    }, []);

    useEffect(() => {
        getShipZone(zoneId);
    }, [zoneId]);

    useEffect(() => {
        if (isRefreshMethodData) {
            getShipZone(zoneId);
        }
    }, [isRefreshMethodData]);

    const pageTitle = zoneID > 0 || zoneId > 0 ? 'Update Shipping Zone' : 'Add Shipping Zone';
    const saveLabel = zoneId > 0 ? 'Save changes' : 'Save shipping zone';
    const hasZoneId = Number(zoneId) > 0;
    const methodCount = shippingMethods.filter(Boolean).length;

    const rowMarkup = shippingMethods.map((method, index) =>
        method ? (
            <IndexTable.Row id={method.id} key={method.id || index} position={index}>
                <IndexTable.Cell>
                    <VerticalStack gap="1">
                        <Text variant="bodyMd" fontWeight="semibold" as="span">
                            {method.title}
                        </Text>
                        <div className="shipping-zone-mth-btn-action">
                            <ButtonGroup>
                                <Button
                                    plain
                                    onClick={() => editShippingMethod(method.id, method.ship_method)}
                                >
                                    Edit
                                </Button>
                                <Button
                                    plain
                                    destructive
                                    onClick={() =>
                                        openDeleteConfirmation(method.id, method.ship_method)
                                    }
                                >
                                    Delete
                                </Button>
                            </ButtonGroup>
                        </div>
                    </VerticalStack>
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <CheckboxMethodStatus
                        defaultValue={method.status}
                        methodName={method.ship_method}
                        methodId={method.id}
                    />
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <VerticalStack gap="1">
                        <Badge>{toTitleCase(method.ship_method.replace(/_/g, ' '))}</Badge>
                        {method.ship_desc ? (
                            <Text variant="bodySm" as="p" color="subdued">
                                {method.ship_desc}
                            </Text>
                        ) : null}
                    </VerticalStack>
                </IndexTable.Cell>
            </IndexTable.Row>
        ) : null
    );

    return (
        <>
            <div className="addshipmethod">
                <Page
                    backAction={{ content: 'Shipping zones', onAction: navigateHome }}
                    title={pageTitle}
                    subtitle="Define where this zone applies and which shipping methods customers can choose."
                    titleMetadata={
                        <Badge status={isZoneChecked ? 'success' : 'attention'}>
                            {isZoneChecked ? 'Enabled' : 'Disabled'}
                        </Badge>
                    }
                    primaryAction={{
                        content: saveLabel,
                        onAction: saveAction,
                        loading: isSaving,
                        disabled: !isLoaded || isSaving,
                    }}
                >
                    {!isLoaded && <Loading />}

                    {!isLoaded ? (
                        <Card padding="4">
                            <div className="loader-shippment">
                                <Box padding="5">
                                    <HorizontalStack align="center" blockAlign="center" gap="3">
                                        <Spinner accessibilityLabel="Loading shipping zone" size="large" />
                                        <Text variant="headingMd" as="h2">
                                            Loading...
                                        </Text>
                                    </HorizontalStack>
                                </Box>
                            </div>
                        </Card>
                    ) : (
                        <VerticalStack gap="5">
                            <Card padding="4">
                                <Layout>
                                    <Layout.AnnotatedSection
                                        id="zone-details"
                                        title="Zone details"
                                        description="Give this zone a clear name so your team can identify it when managing rates."
                                    >
                                        <FormLayout>
                                            <TextField
                                                label="Zone name"
                                                value={zoneName}
                                                onChange={handleZoneNameChange}
                                                autoComplete="off"
                                                placeholder="e.g. Domestic US, EU Express"
                                                helpText="Shown only in your admin — customers do not see this name."
                                                error={errors.zoneName}
                                            />
                                        </FormLayout>
                                    </Layout.AnnotatedSection>

                                    <Layout.AnnotatedSection
                                        id="zone-coverage"
                                        title="Coverage"
                                        description="Choose the countries or states included in this zone."
                                    >
                                        <FormLayout>
                                            <div className="addshipmethod-region-select">
                                                <Text variant="bodyMd" as="p" fontWeight="medium">
                                                    Zone regions
                                                </Text>
                                                <Box paddingBlockStart="1" paddingBlockEnd="1">
                                                    <Select
                                                        options={optionList}
                                                        placeholder="Select countries or states in this zone"
                                                        value={selectedOptions}
                                                        onChange={handleSelect}
                                                        isSearchable
                                                        isMulti
                                                        classNamePrefix="shipzone-region"
                                                    />
                                                </Box>
                                                <Text
                                                    variant="bodySm"
                                                    as="p"
                                                    color={errors.regions ? 'warning' : 'subdued'}
                                                >
                                                    {errors.regions
                                                        ? errors.regions
                                                        : 'Search and select one or more regions that belong to this zone.'}
                                                </Text>
                                            </div>
                                        </FormLayout>
                                    </Layout.AnnotatedSection>

                                    <Layout.AnnotatedSection
                                        id="zone-postcodes"
                                        title="ZIP / postcodes"
                                        description="Optionally limit this zone to specific ZIP or postcodes within the selected regions."
                                    >
                                        <FormLayout>
                                            <div className="tc-postcode">
                                                <TextField
                                                    label="Limit to specific ZIP / postcodes"
                                                    value={postCode}
                                                    onChange={handlePostCodeChange}
                                                    multiline={4}
                                                    placeholder="10001, 10002, 10003"
                                                    autoComplete="off"
                                                    helpText="Optional. Enter codes separated by commas or one per line. Leave blank to include all postcodes in the selected regions."
                                                />
                                            </div>
                                        </FormLayout>
                                    </Layout.AnnotatedSection>
                                </Layout>
                            </Card>

                            <Card>
                                <Box padding="4">
                                    <VerticalStack gap="4">
                                        <HorizontalStack align="space-between" blockAlign="center" wrap gap="3">
                                            <VerticalStack gap="1">
                                                <Text variant="headingMd" as="h2">
                                                    Shipping methods
                                                </Text>
                                                <Text variant="bodySm" as="p" color="subdued">
                                                    Rates and pickup options offered within this zone.
                                                </Text>
                                            </VerticalStack>
                                            <Button
                                                primary
                                                disabled={!hasZoneId}
                                                onClick={openShipMethod}
                                            >
                                                Add shipping method
                                            </Button>
                                        </HorizontalStack>

                                        {!hasZoneId ? (
                                            <Text variant="bodySm" as="p" color="subdued">
                                                Save the shipping zone before adding methods.
                                            </Text>
                                        ) : null}

                                        {methodCount === 0 ? (
                                            <EmptyState
                                                heading="No shipping methods yet"
                                                action={
                                                    hasZoneId
                                                        ? {
                                                              content: 'Add shipping method',
                                                              onAction: openShipMethod,
                                                          }
                                                        : undefined
                                                }
                                                image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
                                            >
                                                <p>
                                                    {hasZoneId
                                                        ? 'Add flat rate, free shipping, local pickup, table rate, or distance-based methods for this zone.'
                                                        : 'Save this zone first, then add the methods customers will see at checkout.'}
                                                </p>
                                            </EmptyState>
                                        ) : (
                                            <div className="tc-index-table">
                                                <IndexTable
                                                    resourceName={resourceName}
                                                    itemCount={methodCount}
                                                    headings={[
                                                        { title: 'Method title' },
                                                        { title: 'Enabled' },
                                                        { title: 'Type & description' },
                                                    ]}
                                                    selectable={false}
                                                >
                                                    {rowMarkup}
                                                </IndexTable>
                                            </div>
                                        )}
                                    </VerticalStack>
                                </Box>
                            </Card>
                        </VerticalStack>
                    )}
                </Page>
            </div>

            <LocalPickup
                localActiveModel={localActiveModel}
                setIsRefreshMethodData={setIsRefreshMethodData}
                getShipZone={getShipZone}
                setLocalActiveModel={setLocalActiveModel}
                zoneMethodId={zoneLocalPickupMethodId}
                setApiResObj={setApiResObj}
            />
            <FlatRate
                flatActiveModel={flatActiveModel}
                setIsRefreshMethodData={setIsRefreshMethodData}
                getShipZone={getShipZone}
                setFlatActiveModel={setFlatActiveModel}
                zoneMethodId={zoneFlatRateMethodId}
                setApiResObj={setApiResObj}
            />
            <FreeShipping
                freeActiveModel={freeActiveModel}
                setIsRefreshMethodData={setIsRefreshMethodData}
                getShipZone={getShipZone}
                setFreeActiveModel={setFreeActiveModel}
                zoneMethodId={zoneFreeMethodId}
                setApiResObj={setApiResObj}
            />

            <AddShipMethod
                showModal={showModal}
                setShowModal={setShowModal}
                zoneId={zoneId}
                setIsRefreshMethodData={setIsRefreshMethodData}
                setApiResObj={setApiResObj}
            />

            <Modal
                open={delActiveConfirm}
                onClose={closeDeleteConfirmation}
                title="Delete shipping method"
                primaryAction={{
                    destructive: true,
                    content: 'Delete',
                    onAction: delShippingMethod,
                }}
                secondaryActions={[
                    {
                        content: 'Cancel',
                        onAction: closeDeleteConfirmation,
                    },
                ]}
            >
                <Modal.Section>
                    <TextContainer>
                        <HorizontalStack align="center">
                            <Icon source={DeleteMinor} color="critical" />
                        </HorizontalStack>
                        <Text variant="headingMd" as="h5">
                            Delete this shipping method?
                        </Text>
                        <p>
                            This action cannot be undone. Customers will no longer see this method at
                            checkout.
                        </p>
                    </TextContainer>
                </Modal.Section>
            </Modal>
        </>
    );
};

export default AddShipZone;
