import React from 'react'
import {
    FormLayout, TextField, Loading, Text,
    Button, IndexTable, LegacyCard, Modal, TextContainer, Checkbox, Icon, FullscreenBar, Toast
} from '@shopify/polaris';
import { DeleteMajor, DeleteMinor } from "@shopify/polaris-icons";
import { useState, useCallback, useMemo, useEffect } from 'react';
import { useLocation, Link, useNavigate, useParams } from "react-router-dom";

import { ContextualSaveBar } from '@shopify/app-bridge-react';
import CheckboxMethodStatus from './CheckboxMethodStatus';
import { useToast } from '@shopify/app-bridge-react';
import axios from 'axios';
import Select from "react-select";
import LocalPickup from '../localpickup/LocalPickup';
import FlatRate from '../flatrate/FlatRate';
import FreeShipping from '../freeshipping/FreeShipping';

import AddShipMethod from './AddShipMethod';

const AddShipZone = () => {
    const { show } = useToast();
    const params = useParams();
    const [isLoaded, setIsLoaded] = useState(false);
    const zoneID = (params.ZoneID == undefined) ? 0 : params.ZoneID;
    const [countZone, setCountZone] = useState(0);
    const [apiResObj, setApiResObj] = useState({
        status: false,
        msg: ""
    });

    useEffect(() => {
        if (zoneID > 0) {
            setIsLoaded(true);
        } else {
            setIsLoaded(true);
        }

    }, [zoneID])
    const navigate = useNavigate();
    const navigateHome = () => {
        navigate('/');
    };
    const navigateTableRate = (id) => {
        navigate('/rate/' + id);
    };

    // const [active, setActive] = useState(false);
    // const toggleActive = useCallback(() => setActive((active) => !active), []);

    const [isRefreshMethodData, setIsRefreshMethodData] = useState(false);

    const [isFullscreen, setFullscreen] = useState(true);
    const fullscreenBarMarkup = (
        <FullscreenBar onAction={navigateHome}>
            <div
                style={{
                    display: 'flex',
                    flexGrow: 1,
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingLeft: '1rem',
                    paddingRight: '1rem',
                }}
            >
                <div style={{ marginLeft: '1rem', flexGrow: 1 }}>
                    <Text variant="headingLg" as="p">
                    </Text>
                </div>
            </div>
        </FullscreenBar>
    );
    // const [zoneMethodId, setMethodDelConfirm] = useState(false);

    const [showSavebar, setShowSavebar] = useState(false);
    const [zoneId, setZoneId] = useState(zoneID);
    const [zoneName, setZoneName] = useState('');
    const handleZoneNameChange = useCallback((newValue) => {
        setZoneName(newValue);
        if (newValue) {
            setShowSavebar(true);
        } else {
            setShowSavebar(false);
        }
    }, []);
    const [isZoneChecked, setIsZoneChecked] = useState(false);
    const handleIsZoneChange = useCallback((newChecked) => { setShowSavebar(true); setIsZoneChecked(newChecked); }, []);
    /** zone method edit settings variable **/
    const [localActiveModel, setLocalActiveModel] = useState(false);
    const [flatActiveModel, setFlatActiveModel] = useState(false);
    const [freeActiveModel, setFreeActiveModel] = useState(false);
    const [zoneMethodId, setZoneMethodId] = useState(0);
    const [zoneFlatRateMethodId, setZoneFlatRateMethodId] = useState(0);
    const [zoneFreeMethodId, setZoneFreeMethodId] = useState(0);
    const [zoneLocalPickupMethodId, setZoneLocalPickupMethodId] = useState(0);
    const [methodDelConfirm, setMethodDelConfirm] = useState(false);
    const [delMethodName, setDelMethodName] = useState('');
    // const DeleteConfirmation = useCallback(() => setDelActive(!delActive), [delActive]);
    const [delActiveConfirm, setDelActiveConfirm] = useState(false);
    const DeleteConfirmation = useCallback((id, method) => {
        if (id) {
            setDelActiveConfirm((delActiveConfirm) => !delActiveConfirm);
            setZoneMethodId(id);
            setDelMethodName(method);
            setMethodDelConfirm(!methodDelConfirm), [methodDelConfirm];
        }
    });
    /** zone method edit settings variable **/
    const [methodId, setMethodId] = useState(0);
    const [postCode, setPostCode] = useState('');

    const handlePostCodeChange = useCallback((newValue) => { setShowSavebar(true); setPostCode(newValue); }, []);

    const [selectedCountry, setSelectedCountry] = useState([]);
    let optionList = [];
    const [selectedOptions, setSelectedOptions] = useState();
    const [selectedZoneOptions, setSelectedZoneOptions] = useState([]);
    // useEffect (() => {
    selectedCountry.map((country, index) => {
        let data = {
            value: (country.state_name) ? `${country.country_code}:${country.state_code}` : country.country_code,
            label: (country.state_name) ? `${country.state_name},${country.country_name}` : country.country_name
        }
        optionList.push(data);
    })



    function handleSelect(data) {
        let region = [];
        data.map((country, index) => {
            optionList.push(data);
            region.push(country.value);
        })
        setSelectedZoneOptions(region);
        setSelectedOptions(data);
        setShowSavebar(true);
    }
    useEffect(() => {
        getCountries();
    }, []);

    useEffect(() => {
        getShipZone(zoneId);
    }, [zoneId]);

    function editShippingMethod(id, shipMethod) {
        if (shipMethod === "local_pickup") {
            setLocalActiveModel(true);
            setZoneLocalPickupMethodId(id);
            setIsRefreshMethodData(false);
            return false;
        } else if (shipMethod === "flat_rate") {
            setFlatActiveModel(true);
            setZoneFlatRateMethodId(id);
            setIsRefreshMethodData(false);
            return false;
        } else if (shipMethod === "free_shipping") {
            setFreeActiveModel(true);
            setZoneFreeMethodId(id);
            setIsRefreshMethodData(false);
            return false;
        } else {
            setLocalActiveModel(false);
            setFlatActiveModel(false);
            setFreeActiveModel(false);
            navigateTableRate(id);
            return false;
        }


    }
    const URLs = {
        'flat_rate': '/v1/flat-rate-shipping',
        'free_shipping': '/v1/free-rate-shipping',
        'local_pickup': '/v1/local-pickup-shipping',
        'table_rate': '/v1/table-rate-shipping',
    };

    function delShippingMethod() {
        //lat URL = URLs[]+'/'+zoneMethodId;
        let id = (zoneMethodId != 0) ? zoneMethodId : 0;
        let URL = URLs[delMethodName] + '/' + zoneMethodId;
        if (id) {
            axios.delete(URL + "?shop=" + Config.shop)
                .then(res => {
                    if (res.status == 200) {
                        setDelActiveConfirm(false);
                        getShipZone(zoneId);
                        show(res.data.msg, { duration: 2000 });
                        return false;
                    } else {
                        show(res.data.msg, { duration: 2000, isError: true });
                        return false;
                    }
                }
                )
        }
    }

    const [customers, setCustomers] = useState([]);
    const resourceName = {
        singular: 'customer',
        plural: 'customers',
    };
    function toTitleCase(str) {
        const titleCase = str
            .toLowerCase()
            .split(' ')
            .map(word => {
                return word.charAt(0).toUpperCase() + word.slice(1);
            })
            .join(' ');

        return titleCase;
    }

    const rowMarkup = customers.map((method, index) => (
        (method) ?
            <IndexTable.Row id={method.id} key={index} position={index} >
                <IndexTable.Cell>
                    {method.title}
                    {/* <ButtonGroup> */}
                    <div className="shipping-zone-mth-btn-action">
                        <Button plain onClick={() => { editShippingMethod(method.id, method.ship_method) }}>Edit</Button>&nbsp;
                        <Button plain destructive onClick={() => { DeleteConfirmation(method.id, method.ship_method) }} >Delete</Button>
                    </div>
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <CheckboxMethodStatus defaultValue={method.status} methodName={method.ship_method} methodId={method.id} />
                </IndexTable.Cell>
                <IndexTable.Cell>
                    <Text variant="headingMd" as="h6">
                        {toTitleCase(method.ship_method.replace(/_/g, " "))}
                    </Text>
                    <p>{method.ship_desc}</p>
                </IndexTable.Cell>
            </IndexTable.Row>

            : ""
    ),

    );
    const [showModal, setShowModal] = useState(false);
    const openShipMethod = () => {
        setShowModal(true);
        setIsRefreshMethodData(false);
    }
    useEffect(() => {
        if (isRefreshMethodData) {
            getShipZone(zoneId);
        }
    }, [isRefreshMethodData]);

    const getCountries = () => {
        fetch("/countries?shop=" + Config.shop).then(res => res.json()).then((result) => {
            if (result.status === 1) {
                // console.log(result, "ankit")
                setSelectedCountry(result.country);
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true });
        })
    }

    const getShipZone = (id) => {
        setIsLoaded(false);
        fetch("/shipzone/" + id + "?shop=" + Config.shop).then(res => res.json()).then((result) => {
            if (result.status === 1) {
                if (result.zone_mthd) {
                    setCustomers(result.zone_mthd);
                    setZoneName(result.zone_exist.ship_zone);
                    setIsZoneChecked(result.zone_exist.status);
                    // setZoneSelected({ label: result.zone_exist.preority, value: result.zone_exist.preority });
                    setCountZone(result.zoneCount);
                    setSelectedOptions(result.zone_exist.zone_region);
                } else {
                    setCountZone(result.zoneCount);
                }

                setIsLoaded(true);
            }
        }, (error) => {
            show(error, { duration: 2000, isError: true });
            setIsLoaded(true);
        })
    }

    const saveAction = () => {
        // disabled: false,
        // loading: false,
        // onAction: () => {

        if (zoneName.length <= 0) {
            show("Please Enter Zone Name.", { duration: 2000, isError: true });
            setShowModal(false);
            return false;
        }
        setIsLoaded(false);
        fetch("/shipzone", {
            method: "POST",
            body: JSON.stringify({
                'zoneId': zoneId,
                'shop': Config.shop,
                'shipzone': zoneName,
                'status': isZoneChecked,
                'region': selectedOptions,
                'data': selectedZoneOptions,
                'postcode': postCode,
                '_token': Config.csrf_token
            }),
            headers: {
                "Content-type": "application/json"
            }
        }).then(res => res.json()).then((result) => {
            if (result.status === 1) {
                console.log("Welcome", result.zond_id, result.msg)
                show(result.msg, { duration: 2000 });

                setZoneId(result.zond_id);
                // setApiResObj(result);
                setShowModal(false);
            } else {
                show(result.msg, { duration: 5000, isError: true });
            }
            setIsLoaded(true);
            setShowSavebar(false);
        });
        // }
    };
    const discardAction = {
        disabled: false,
        loading: false,
        discardConfirmationModal: true,
        onAction: () => console.log('On discard action')
    };
    return (
        <>
            {!isLoaded && <Loading />}
            {/* <ContextualSaveBar
                saveAction={saveAction}
                discardAction={discardAction}
                fullWidth
                leaveConfirmationDisable
                visible={showSavebar}
            /> */}


            <div className="addshipmethod">
                <div style={{ width: '100%' }}>
                    {isFullscreen && fullscreenBarMarkup}
                    {/* <div style={{ padding: '1rem' }}></div> */}
                </div>
                <LegacyCard sectioned>
                    <FormLayout>
                        <TextField
                            label="Zone Name"
                            value={zoneName}
                            onChange={handleZoneNameChange}
                            autoComplete="off"
                            placeholder="Zone Name"
                        />
                        <label>Zone Regions</label>
                        <Select
                            options={optionList}
                            placeholder="Select regions with in this zone"
                            value={selectedOptions}
                            onChange={handleSelect}
                            isSearchable={true}
                            isMulti
                        />
                        <div className='tc-postcode'>
                            <TextField
                                label="Limit to specific ZIP/postcodes"
                                value={postCode}
                                onChange={handlePostCodeChange}
                                multiline={4}
                                placeholder="List postcode with comma separated"
                                autoComplete="off"
                            />

                        </div>
                        <Checkbox
                            label="Shipping Zone (Enable/Disable)"
                            checked={isZoneChecked}
                            onChange={handleIsZoneChange}
                        />
                        {/* <label>Zone Priority</label>
                        <Select
                            options={zoneOptions}
                            onChange={handleZoneChange}
                            value={zoneSelected}
                        /> */}
                        <Button primary onClick={saveAction}>Save Shipping Zone</Button>
                        {/* <Combobox
                            allowMultiple
                            activator={
                                <Combobox.TextField
                                    autoComplete="off"
                                    label="Zone Regions"
                                    labelHidden
                                    value={value}
                                    suggestion={suggestion}
                                    verticalContent={verticalContentMarkup}
                                    onChange={setValue}
                                />
                            }
                        >
                            {listboxMarkup}
                        </Combobox> */}
                        <label>Shipping Method</label>
                        <LegacyCard>
                            <div className='tc-index-table'>
                                <IndexTable
                                    resourceName={resourceName}
                                    itemCount={customers.length}
                                    headings={[
                                        { title: 'Shipping Method Title' },
                                        { title: 'Enabled' },
                                        { title: 'Description' },
                                    ]}
                                    selectable={false}
                                >
                                    {rowMarkup}
                                </IndexTable>
                            </div>

                        </LegacyCard>
                        <div className='addshipzonetack'>
                            <Button primary disabled={!zoneId} onClick={() => {
                                //contextualSaveBar.dispatch(ContextualSaveBar.Action.SHOW);
                                openShipMethod();
                            }}>
                                Add Shipping Method
                            </Button>
                        </div>

                    </FormLayout>
                </LegacyCard>


            </div>



            <LocalPickup localActiveModel={localActiveModel} setIsRefreshMethodData={setIsRefreshMethodData} getShipZone={getShipZone} setLocalActiveModel={setLocalActiveModel} zoneMethodId={zoneLocalPickupMethodId} setApiResObj={setApiResObj} />
            <FlatRate flatActiveModel={flatActiveModel} setIsRefreshMethodData={setIsRefreshMethodData} getShipZone={getShipZone} setFlatActiveModel={setFlatActiveModel} zoneMethodId={zoneFlatRateMethodId} setApiResObj={setApiResObj} />
            <FreeShipping freeActiveModel={freeActiveModel} setIsRefreshMethodData={setIsRefreshMethodData} getShipZone={getShipZone} setFreeActiveModel={setFreeActiveModel} zoneMethodId={zoneFreeMethodId} setApiResObj={setApiResObj} />

            <AddShipMethod
                showModal={showModal}
                setShowModal={setShowModal}
                zoneId={zoneId}
                setIsRefreshMethodData={setIsRefreshMethodData}
                setApiResObj={setApiResObj}
            />

            <Modal
                // activator={activator}
                open={delActiveConfirm}
                onClose={DeleteConfirmation}
                // title=""
                primaryAction={{
                    destructive: 1,
                    content: 'Delete',
                    onAction: delShippingMethod,
                }}
                secondaryActions={[
                    {
                        content: 'Cencel',
                        onAction: DeleteConfirmation,
                    },
                ]}
            >
                <Modal.Section>
                    <TextContainer>
                        <Icon source={DeleteMinor} color="critical" />
                        <Text variant="headingLg" as="h5">
                            Are you sure? ?
                        </Text>
                        <p>
                            You want be able to delete this!
                        </p>
                    </TextContainer>
                </Modal.Section>
            </Modal>

            {/* {apiResObj.msg && <Toast content={apiResObj.msg} duration={2000} isError={!apiResObj.status} onDismiss={() => {
                setApiResObj({
                    status: 0,
                    msg: ""
                });
            }} />} */}
        </>
    )
}

export default AddShipZone
