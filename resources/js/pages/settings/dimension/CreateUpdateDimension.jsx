import React, { useState, useCallback, useRef, useEffect } from 'react'
import {
    FormLayout, TextField, Loading,
    Button, Page, Select, Box, Card,
    Layout, Spinner, Text, Checkbox
} from '@shopify/polaris';
import { useNavigate, useParams } from "react-router-dom";
import { useToast } from '@shopify/app-bridge-react';

const CreateUpdateDimension = () => {
    const { show } = useToast();
    const params = useParams();
    const navigate = useNavigate();
    const navigateHome = () => {
        navigate('/pages/settings?tab=dimension');
    };
    const packageID = (params.id == undefined) ? 0 : params.id;
    console.log(packageID, "packageID");
    const [isLoaded, setIsLoaded] = useState(false);
    const [errors, setErrors] = useState({
        "package":"",
        'length': "",
        'width': "",
        'height': "",
        'weight': "",
        'currency': "",
    });
    useEffect(() => {
        if (packageID > 0) {
            getPackageDimension(packageID);
        }
    }, [packageID])
    const [packageName, setPackageName] = useState("");
    const handlePackageChange = useCallback((newValue) => {
        if (newValue != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['package']: "",
            }));
        }
        setPackageName(newValue)
    }, []);
    const dimesionOptions = [
        { label: 'Inch', value: 'inch' },
        { label: 'Centimetre ', value: 'cms' },
        { label: 'Inches', value: 'inches' },
    ];
    const [selectDimension, setSelectDimension] = useState('inch');
    const handleDimensionChange = useCallback((value) => {
        setSelectDimension(value)  
    }, []);

    const [length, setLength] = useState(null);
    const handleLengthChange = useCallback((newValue) => {
        if (newValue != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['length']: "",
            }));
        }
        setLength(newValue)
    }, []); 

    
    const [width, setWidth] = useState(null);
    const handleWidthChange = useCallback((newValue) => {
        if (newValue != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['width']: "",
            }));
        }
        setWidth(newValue)
    }, []); 

    const [height, setHeight] = useState(null);
    const handleHeightChange = useCallback((newValue) => {
        if (newValue != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['height']: "",
            }));
        }
        setHeight(newValue)
    }, []); 

    const [weight, setWeight] = useState(null);
    const handleWeight = useCallback((newValue) => {
        if (newValue != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['weight']: "",
            }));
        }
        setWeight(newValue)
    }, []); 


    const weightOptions = [
        { label: 'LB', value: 'lb' },
        { label: 'LBS', value: 'lbs' },
        { label: 'Kilogram', value: 'kgs' },
        { label: 'Grams', value: 'gms' },
    ];
    const [weightDimension, setWeightDimension] = useState('lb');
    const handleWeightChange = useCallback((value) => setWeightDimension(value), []);

    const [currency, setCurrency] = useState("");
    const handleCurrencyChange = useCallback((value) => {
        if (value != "") {
            setErrors((prevData) => ({
                ...prevData,
                ['currency']: "",
            }));
        }
        setCurrency(value)
    }, []);
    const validateForm = () => {
        let valid = true;
        const newErrors = { ...errors };
        if (!packageName || packageName.trim() === "") {
            newErrors.package = "This field required";
            valid = false;
        }
        if (!length || length < 1) {
            newErrors.length = "This field required";
            valid = false;
        }
        if (!width || width < 1) {
            newErrors.width = "This field required";
            valid = false;
        }
        if (!height || height < 1) {
            newErrors.height = "This field required";
            valid = false;
        }
        if (!weight || weight < 1) {
            newErrors.weight = "This field required";
            valid = false;
        }
        setErrors(newErrors);
        return valid;
    };
    const getPackageDimension = (id) => {
        setIsLoaded(true)
        fetch("/dimension/" + id + "?shop=" + Config.shop).then(res => res.json()).then((result) => {
            if (result.status) {
                setPackageName(result.data.name);
                setSelectDimension(result.data.dimension_unit);
                setLength(result.data.length);
                setWidth(result.data.width);
                setHeight(result.data.height);
                setWeight(result.data.weight);
                setWeightDimension(result.data.weight_unit);
                setCurrency(result.data.currency);
            }
            setIsLoaded(false);
        }, (error) => {
            show(error, { duration: 2000, isError: true });
            setIsLoaded(false);
        });
    }
    const saveAction = () => {
        console.log(validateForm(), "validateForm");
        console.log(packageName, selectDimension, length, width, height, weight, weightDimension, currency,"formData");
        if (validateForm()) {
            let url = "";
            let method = "";
            if (packageID > 0) {
                url = "/dimension/" + packageID;
                method = "PUT";
            } else {
                url = "/dimension";
            }
            fetch(url, {
                method: "POST",
                body: JSON.stringify({
                    shop: Config.shop,
                    package: packageName,
                    dimension_unit: selectDimension,
                    length: length,
                    width: width,
                    height: height,
                    weight: weight,
                    weight_unit: weightDimension,
                    currency: currency,
                    _token: Config.csrf_token,
                    _method: method
                }),
                headers: {
                    "Content-type": "application/json",
                },
            })
                .then((res) => res.json())
                .then((result) => {
                    if (result.status) {
                        show(result.message, { duration: 2000 });
                        setTimeout(function () { navigateHome(); }, 2000);
                    } else {
                        // shopify.toast.show(result.message, { duration: 5000, isError: true });
                        show(result.message, { duration: 2000, isError: true });
                        if (result.errors) {
                            setErrors({
                                package: result.errors.package || "",
                                length: result.errors.length || "",
                                width: result.errors.width || "",
                                height: result.errors.height || "",
                                weight: result.errors.weight || "",
                            });
                        }
                    }
                    // setIsLoaded(true);
                    // setShowSavebar(false);
                });
        }
    }
    return (
        <div className="add-package-dimension">
            <Page backAction={{ content: 'Settings', onAction: (() => { navigateHome(); }) }} title={packageID > 0 ? "Update Box Dimension" : "Add Box Dimension"} >
                <Card padding="4">
                    {isLoaded ?
                        <div className='loader-shippment'>
                            <Box padding="500">
                                <Spinner accessibilityLabel="Spinner example" size="large" />
                                <Text variant="headingMd" as="h2">Loading ...</Text>
                            </Box>
                        </div> :
                        <Box padding="500">
                            <Layout>
                            <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Package Name"
                                    description="Configure the name of the package"
                                >
                                    <FormLayout>
                                        <TextField
                                            // label="Length"
                                            type="text"
                                            value={packageName}
                                            onChange={handlePackageChange}
                                             error={errors.package}
                                            autoComplete="off"
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="Dimension_unit"
                                    title="Dimension Unit"
                                    description="Only select the dimension unit set up for your package"
                                >
                                    <FormLayout>
                                        <Select
                                            // label="Dimension Unit"
                                            options={dimesionOptions}
                                            onChange={handleDimensionChange}
                                            value={selectDimension}
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Package Length"
                                    description="Configure the package length"
                                >
                                    <FormLayout>
                                        <TextField
                                            // label="Length"
                                            type="number"
                                            value={length}
                                            onChange={handleLengthChange}
                                             error={errors.length}
                                            autoComplete="off"
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Package Width"
                                    description="Configure the package width"
                                >
                                    <FormLayout>
                                        <TextField
                                            // label="Length"
                                            type="number"
                                            value={width}
                                            onChange={handleWidthChange}
                                            error={errors.width}
                                            autoComplete="off"
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Package Height"
                                    description="Configure the package height"
                                >
                                    <FormLayout>
                                        <TextField
                                            // label="Length"
                                            type="number"
                                            value={height}
                                            onChange={handleHeightChange}
                                            error={errors.height}
                                            autoComplete="off"
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Package Weight"
                                    description="Configure the package weight"
                                >
                                    <FormLayout>
                                        <TextField
                                            // label="Length"
                                            type="number"
                                            value={weight}
                                            onChange={handleWeight}
                                            error={errors.weight}
                                            autoComplete="off"
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                                <Layout.AnnotatedSection
                                    id="storeDetails"
                                    title="Weight Unit"
                                    description="Only select the weight unit set up for your package"
                                >
                                    <FormLayout>
                                        <Select
                                            // label="Dimension Unit"
                                            options={weightOptions}
                                            onChange={handleWeightChange}
                                            value={weightDimension}
                                        />
                                    </FormLayout>
                                </Layout.AnnotatedSection>
                            </Layout>
                            <Button variant="primary" onClick={saveAction}>{packageID ? 'Update Changes' : 'Save Changes'}</Button>
                        </Box>}
                </Card>
            </Page>
        </div>
    )
}

export default CreateUpdateDimension
