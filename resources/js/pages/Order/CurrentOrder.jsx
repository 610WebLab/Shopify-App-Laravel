import React, { useEffect, useState, useCallback } from 'react'
import {
  TextField,
  IndexTable,
  LegacyCard,
  IndexFilters,
  useSetIndexFiltersMode,
  useIndexResourceState,
  Text,
  ChoiceList,
  RangeSlider,
  Badge,
  IndexFiltersMode,
  useBreakpoints,
  Button,
  Icon,
  ButtonGroup,
  Select,
  OptionList,
  Popover,
  ActionList,
  Pagination,
  Loading
} from '@shopify/polaris';
import axios from "axios";
import { useToast } from '@shopify/app-bridge-react';
import {useLocation, useNavigate } from "react-router-dom";

import RatesListingModal from './RatesListingModal'

const CurrentOrder = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { show } = useToast();
  const [optionSelected, setOptionSelected] = useState([]);
  const [popoverActive, setPopoverActive] = useState([]);
  const [isLoaded, setIsLoaded] = useState(false);
  // const togglePopoverActive = useCallback((orderId) => {
  //   // setPopoverActive((popoverActive) => !popoverActive)
  //   setPopoverActive((prev) => ({
  //     ...prev,
  //     [orderId]: !popoverActive,
  //   }));
  // }, []);

  const activator = (orderId) => {


  }

  console.log(location.pathname, "Current order");


  // console.log(popoverActive, "popoverActive")
  const [orders, setOrders] = useState([]);
  const [loader, setLoader] = useState(true);
  const [current, setCurrent] = useState(null);
  const { mode, setMode } = useSetIndexFiltersMode(IndexFiltersMode.Filtering);
  const [queryValue, setQueryValue] = useState(undefined);
  const handleQueryValueChange = useCallback((value) => setQueryValue(value), []);
  const handleQueryValueRemove = useCallback(() => setQueryValue(''), []);
  const handleFiltersClearAll = useCallback(() => { handleQueryValueRemove(); }, [handleQueryValueRemove]);
  const [selected, setSelected] = useState('today');
  const handleSelectChange = useCallback((value) => setSelected(value), []);
  const defaultService = { label: "Local", value: 0 };
  const [otherCarrierServices, setOtherCarrierServices] = useState([]);
  const [serviceOptions, setServiceOptions] = useState([]);
  const [otherCarrier, setOtherCarrier] = useState([]);
  const [carrierOptions, setCarrierOptions] = useState([]);
  const [carriers, setCarriers] = useState([]);
  const [active, setActive] = useState(false);
  const [loading, setLoading] = useState(false)
  const [ratesData, setRatesData] = useState([])
  const [selectedOrderId, setSelectedOrderId] = useState(null);
  const [selectedserviceId, setSelectedServiceId] = useState(null);
  const [selectedCarrierId, setSelectedCarrierId] = useState(null);
  const [labelTemplateOptions, setLabelTemplateOptions] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);
  const [toastError, setToastError] = useState(false);
  const [shippingZonesData, setShippingZonesData] = useState([]);
  const [groupTitle, setGroupTitle] = useState([]);
  const [loadingOrders, setLoadingOrders] = useState({});
  const [popoverActiveAction, setPopoverActiveAction] = useState({});
  const [selectedOptions, setSelectedOptions] = useState([]);
  const [groupedOptions, setGroupedOptions] = useState([]);

  const [boxOptions, setBoxOptions] = useState([])
  const [boxPackage, setBoxPackage] = useState([]);
  const [selectedPackage, setSelectedPackage] = useState({});

  const togglePopoverActiveAction = (orderId) => {
    setPopoverActiveAction((prev) => ({
      ...prev,
      [orderId]: !prev[orderId],
    }));
  };
  useEffect(() => {
    setLoading(true);

    const getShippingzones = fetch("/get-shipping-zone?shop=" + Config.shop).then((res) => res.json()).then((result) => result.shopZone);
    const getOtherCarrierService = fetch("/other-carrier-service?shop=" + Config.shop + "&page=carrier").then((res) => res.json()).then((result) => result.data);

    Promise.all([getShippingzones, getOtherCarrierService])
      .then(([tableAData, tableBData]) => {
        const allOptions = [...tableAData, ...tableBData];
        setShippingZonesData(tableAData);
        setOtherCarrierServices(tableBData);
        // Categorize data dynamically
        const categorizedOptions = [
          {
            title: "Local Shipping",
            options: tableAData.filter((option) => option.status == 1).map((option) => ({
              value: option.id, label: option.ship_zone
                ? option.ship_zone.charAt(0).toUpperCase() + option.ship_zone.slice(1)
                : ""
            })),
          },
          {
            title: "Other Shipping",
            options: tableBData.map((option) => ({
              value: option.id, label: option.carrier_type
                ? option.carrier_type.charAt(0).toUpperCase() + option.carrier_type.slice(1)
                : ""
            })),
          },
        ];

        setGroupedOptions(categorizedOptions);
      })
      .catch((error) => console.error("Error fetching data:", error))
      .finally(() => setLoading(false));
  }, []);
  const handlePackageChange = useCallback((value, orderId) => {
    setSelectedPackage((prev) => ({
      ...prev,
      [orderId]: value
    }));
  });

  const handleBoxChange = useCallback((value, orderId) => {
    if (value != "") {
      setBoxPackage((prev) => ({
        ...prev,
        [orderId]: parseInt(value)
      }));
    }
  }, []);

  useEffect(() => {
    fetch(`/dimension?shop=${Config.shop}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error("Failed to fetch shipping methods");
        }
        return response.json();
      })
      .then((data) => {
        if (data.status) {
          setBoxPackage(data.data);
          const formattedOptions = data.data.map((item) => ({
            label: item.name,
            value: item.id,
          }));
          setBoxOptions((prevOptions) => [...prevOptions, ...formattedOptions]);
        }
      })
      .catch((err) => console.log(err.message));
  }, []);
  const togglePopoverActive = useCallback((id) => {
    setPopoverActive((prev) => ({
      ...prev,
      [id]: !prev[id],
    }));
  }, []);
  // Handle selection change for a specific row
  const handleSelectOptionChange = (id, value, groupTitle) => {
    console.log("Selected value:", value);

    // Store selected shipping option for each order
    setSelectedOptions((prev) => ({
      ...prev,
      [id]: value,
    }));

    // Toggle the popover state
    setPopoverActive((prev) => ({
      ...prev,
      [id]: !prev[id],
    }));

    // Reset carrier options for the current order only
    setCarrierOptions((prev) => ({
      ...prev,
      [id]: [],
    }));

    // Store group title per order
    setGroupTitle((prev) => ({
      ...prev,
      [id]: groupTitle,
    }));

    const selectedId = value[0];

    if (groupTitle === "Local Shipping" && shippingZonesData) {
      const matchedZone = shippingZonesData.find(
        (zone) => zone.id.toString() === selectedId.toString() && zone.status === 1
      );

      if (matchedZone?.methods) {
        const newCarrierOptions = matchedZone.methods.map((method) => ({
          label: method.title,
          value: method.title,
          id: method.id.toString(),
        }));

        console.log("New Carrier Options:", newCarrierOptions);

        setCarrierOptions((prev) => ({
          ...prev,
          [id]: newCarrierOptions,
        }));
      }
    } else {
      let idv = /\d/.test(selectedId) ? parseInt(selectedId, 10) : selectedId;

      setOtherCarrier((prev) => ({
        ...prev,
        [id]: idv,
      }));

      if (typeof idv === "number" && idv > 0) {
        const service = otherCarrierServices?.find((item) => item.id === idv);

        if (service?.carrier) {
          const newCarrierOptions = service.carrier.map((c) => ({
            label: c.name,
            value: c.name,
            id: c.id.toString(),
          }));

          setCarrierOptions((prev) => ({
            ...prev,
            [id]: newCarrierOptions, // ✅ Store per order ID
          }));
        }
      }
    }
  };
  const handleModalChange = useCallback(() => setActive(!active), [active]);

  const [selectedService, setSelectedService] = useState('local');
  const [selectedCarrier, setSelectedCarrier] = useState('');
  const [selectedLabelTemplate, setSelectedLabelTemplate] = useState([]);


  const handleCarrierChange = useCallback((value, orderId) => {
    const selectedCarrier = carrierOptions[orderId].find(option => option.value == value);
    setCarriers(prev => ({
      ...prev,
      [orderId]: {
        id: selectedCarrier?.id || "",         // Store the selected ID
        label: value || "", // Store the selected Label
      }
    }));
  }, [carrierOptions]);




  /* start pagination  */
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const nextPage = () => {
    if (page < lastPage) {
      setPage(page + 1);
    }
  };
  const prevPage = () => {
    if (page > 1) {
      setPage(page - 1);
    }
  };
  /* end  pagination  */
  const tabs = [];
  const filters = [];
  useEffect(() => {
    getOrderData(page, queryValue);
  }, [page, queryValue]);
  const formatCarrierType = (str) => {
    if (!str) return "";
    if (str.toLowerCase() === "easypost") return "EasyPost";
    return str.charAt(0).toUpperCase() + str.slice(1);
  };



  useEffect(() => {
    fetch("/get-templates?shop=" + Config.shop)
      .then((res) => res.json())
      .then(
        (result) => {
          // console.log('result', result)
          if (result.status) {
            const formattedtemplates = result.templates.length > 0 && result.templates.map(template => ({
              label: template?.name,
              value: template.id,
            }));
            setLabelTemplateOptions(formattedtemplates)
          }
        },
        (error) => {
          console.log('error', error)
        }
      );

  }, [])

  const getOrderData = (page, queryValue) => {
    setLoader(false);
    fetch("/orders?shop=" + Config.shop + "&page=" + page + "&search=" + queryValue + "&type=Current")
      .then((res) => res.json())
      .then(
        (result) => {
          setLoader(true);
          if (result.status) {
            setOrders(result.data.data)
            setPage(result.data.current_page);
            setLastPage(result.data.last_page);
            setCurrentPage(result.data.current_page);
            setTotalPages(result.data.total);

          }
        },
        (error) => {
          setLoader(false);
        }
      );
  }

  useEffect(() => {
    if (orders.length === 0) return;

    // Store default service selections
    const defaultOtherService = orders.reduce((acc, order) => {
      acc[order.id] = order.shipping_service ? [order.shipping_service.toString()] : [""];

      return acc;
    }, {});

    setSelectedOptions(defaultOtherService);

    let carrierOptionsToSet = [];
    let otherCarriersToSet = {};

    orders.forEach((order) => {
      const selectedId = order.shipping_service?.toString();

      if (order.group_title == "Local Shipping" && shippingZonesData) {
        // Find the matching shipping zone with status == 1
        const matchedZone = shippingZonesData.find(
          (zone) => zone.id.toString() === selectedId && zone.status === 1
        );
        console.log('matchedZone', matchedZone)

        if (matchedZone?.methods) {
          // Map the methods to the required format
          carrierOptionsToSet[order.id] = matchedZone.methods.map((method) => ({
            label: method.title,
            value: method.title,
            id: method.id.toString(),
          }));
        }
      } else {
        let idv = /\d/.test(selectedId) ? parseInt(selectedId, 10) : selectedId;

        otherCarriersToSet[order.id] = idv; // Store parsed ID

        if (typeof idv === "number" && idv > 0) {
          const service = otherCarrierServices?.find((item) => item.id === idv);
          if (service?.carrier) {
            carrierOptionsToSet[order.id] = service.carrier.map((c) => ({
              label: c.name,
              value: c.name,
              id: c.id.toString(),
            }));
          }
        }
      }
    });

    setCarrierOptions(carrierOptionsToSet); // ✅ Update carrier options only once
    setOtherCarrier(otherCarriersToSet); // ✅ Batch update otherCarrier state

    // Store default carrier selections
    const defaultCarriers = orders.reduce((acc, order) => {
      const selectedCarrier = carrierOptionsToSet[order.id]?.find((option) => option.value == order?.carrier_label);
      acc[order.id] = {
        id: order.carrier_id ? order.carrier_id : 0,
        label: selectedCarrier?.label || "",
      };
      return acc;
    }, {});

    setCarriers(defaultCarriers);
    const defaultGroupTitle = orders.reduce((acc, order) => {
      acc[order.id] = order.group_title ? order.group_title : '';

      return acc;
    }, {})// ✅ Batch update state

    setGroupTitle(defaultGroupTitle);

    // Store default label templates
    const defaultTemplates = orders.reduce((acc, order) => {
      acc[order.id] = order?.template_id ? parseInt(order.template_id, 10) : "";
      return acc;
    }, {});

    setSelectedLabelTemplate(defaultTemplates);

    const defaultSelectedPackage = orders.reduce((acc, order) => {
      acc[order.id] = order?.label_count ? order?.label_count : "";
      return acc;
    }, {});

    setSelectedPackage(defaultSelectedPackage);

    const defaultBoxPackage = orders.reduce((acc, order) => {
      acc[order.id] = order?.dimension_id ? parseInt(order.dimension_id) : '';
      return acc;
    }, {});

    setBoxPackage(defaultBoxPackage);

  }, [orders, shippingZonesData, otherCarrierServices])

  console.log(boxPackage, 'BoxPackage')
  // console.log("carrierServiceData", otherCarrier, carriers)
  const handleGetShppingRates = async (order_id, service_id, carrier_id, label, dimension_id) => {
    setSelectedOrderId(order_id)
    setSelectedServiceId(service_id)
    setSelectedCarrierId(carrier_id)
    handleModalChange()
    setLoading(true)
    try {
      let response = await fetch(`/get-shpping-rates?shop=${Config.shop}&order_id=${order_id}&service_id=${service_id}&carrier_id=${carrier_id}&dimension_id=${dimension_id}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Failed to fetch shipping rates");
      }

      let data = await response.json();
      setRatesData(data)
      console.log("Shipping Rates:", data);
    } catch (error) {
      console.error("Error fetching shipping rates:", error);
    } finally {
      setLoading(false)
    }

  }

  const handlePurchaseLabel = async (order_id, selectedserviceId, selectedCarrierId, label, template_id, groupTitle, label_count, dimension_id) => {
    if (!order_id) return show("Order ID is required!", { duration: 2000, isError: true });
    // howToast("Order ID is required!", true);
    if (!template_id) return show("Template ID is required!", { duration: 2000, isError: true });
    // showToast("Template ID is required!", true);
    const requestData = {
      order_id: order_id,
      template_id: template_id,
      service_id: selectedserviceId?.[0],
      carrier_id: selectedCarrierId,
      group_title: groupTitle,
      carrier_label: label,
      label_count: label_count,
      dimension_id: dimension_id,

    };


    setIsSubmitting(true);

    try {
      const response = await axios.post(`/create-local-shipping-label?shop=${Config.shop}`, requestData);

      if (response.data.status) {
        togglePopoverActiveAction(order_id);
        show(response.data.message, { duration: 2000 });

        // showToast(response.data.message, false); // Success message
        getOrderData()
        // setTimeout(() => {
        //   window.location.reload();

        // }, 1000)
      } else {
        show(response.data.message || "Failed to create shipping label.", { duration: 2000, isError: true });

        // showToast(response.data.message || "Failed to create shipping label.", true);
      }
    } catch (error) {
      console.error("Error creating shipping label:", error);

      if (error.response) {
        // API responded with a specific error
        const errorMessage = error.response.data.message || "Failed to create shipping label.";
        show(errorMessage, { duration: 2000, isError: true });

        // showToast(errorMessage, true);
      } else {
        // Network or other error
        show("Network error. Please try again.", { duration: 2000, isError: true });

        // showToast("Network error. Please try again.", true);
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  const handlePrintLabel = async (order_id) => {
    if (order_id > 0) {
      fetch(`/print-label/${order_id}?shop=${Config.shop}`)
        .then((response) => {
          // Check if response is a PDF or JSON
          const contentType = response.headers.get("content-type");

          if (contentType && contentType.includes("application/json")) {
            return response.json(); // Handle JSON response
          } else {
            return response.blob(); // Handle PDF response
          }
        })
        .then((data) => {
          if (typeof data === "object" && data.status && data.label_link) {
            // Case 1: If API returns a direct label URL (JSON response)
            togglePopoverActiveAction(order_id)
            window.open(data.label_link, "_blank"); // Open PNG/PDF label in a new tab
          } else if (data instanceof Blob) {
            // Case 2: If API returns a PDF directly (Blob response)
            togglePopoverActiveAction(order_id)
            const pdfUrl = URL.createObjectURL(data);
            window.open(pdfUrl, "_blank"); // Open generated PDF in a new tab
          } else {
            console.error("Unexpected response format:", data);
          }
        })
        .catch((error) => {
          console.error("Error fetching label:", error);
        });
    }
  };


  const handleOrderFulfilled = async (order_id) => {
    if (order_id > 0) {
      // Set loading to true for the specific order
      setLoadingOrders((prev) => ({
        ...prev,
        [order_id]: true,
      }));

      fetch(`/order-fulfilled/${order_id}?shop=${Config.shop}`)
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json(); // Assuming response is JSON
        })
        .then((data) => {
          if (data.status) {
            togglePopoverActiveAction(order_id)
            show(data.message, { duration: 2000 }); // ✅ Success toast
            getOrderData(); // Refresh order data
          } else {
            show(data.message || "Failed to fulfill order.", {
              duration: 2000,
              isError: true, // ❌ Error toast
            });
          }
        })
        .catch((error) => {
          console.error("Error fulfilling order:", error);
          show("Something went wrong. Please try again.", {
            duration: 2000,
            isError: true,
          });
        })
        .finally(() => {
          // Reset loading state after completion
          setLoadingOrders((prev) => ({
            ...prev,
            [order_id]: false,
          }));
        });
    }
  };

  const formatDate = (inputDate) => {
    const options = {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric',
      hour12: true,
    };
    const formattedDate = new Intl.DateTimeFormat('en-US', options).format(new Date(inputDate));
    return formattedDate;
  };
  const resourceName = {
    singular: 'order',
    plural: 'orders',
  };

  const statusConfig = {
    paid: "success",
    pending: "attention",
    refunded: "info"
  };

  const fulfillmentConfig = {
    Fulfilled: { progress: "complete", tone: "" },
    Unfulfilled: { progress: "incomplete", tone: "attention" },
    Canceled: { progress: "complete", tone: "critical" }
  };
  const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(orders);
  const rowMarkup = orders.map(
    (
      { id, customer_email, customer_id, label_url, customer_name, date, delivery_method, delivery_status, fullfilement, item_count, labels, order_id, order_name, order_no, payment_status, tags, total, user_id, created_at }, index,) => (
      <IndexTable.Row
        id={id}
        key={id}
        selected={selectedResources.includes(id)}
        position={index}
        onClick={() => { console.log("Row Click"); }}
      >
        <IndexTable.Cell> <Text variant="bodyMd" fontWeight="bold" as="span">{order_name}</Text></IndexTable.Cell>
        <IndexTable.Cell>{labels}</IndexTable.Cell>
        <IndexTable.Cell>{formatDate(created_at)}</IndexTable.Cell>
        <IndexTable.Cell>
          <Badge status={statusConfig[payment_status]}>
            {payment_status.charAt(0).toUpperCase() + payment_status.slice(1)}
          </Badge>
        </IndexTable.Cell>
        <IndexTable.Cell>
          <Badge progress={fulfillmentConfig[fullfilement]?.progress} status={fulfillmentConfig[fullfilement]?.tone}>
            {fullfilement}
          </Badge>
        </IndexTable.Cell>
        <IndexTable.Cell>{delivery_status}</IndexTable.Cell>
        <IndexTable.Cell>
          <Popover
            active={popoverActive[id] || false}
            disabled={label_url}

            activator={
              <Button onClick={() => togglePopoverActive(id)} disclosure>
                {(() => {
                  // Flatten all options from groupedOptions
                  const flatOptions = groupedOptions.reduce((acc, group) => acc.concat(group.options), []);
                  const selectedVal = selectedOptions[id] ? selectedOptions[id][0] : null;
                  const selectedOption = flatOptions.find(opt => opt.value.toString() === (selectedVal?.toString() || ""));
                  return selectedOption ? selectedOption.label : "Select Option";
                })()}
              </Button>
            }
            onClose={() => togglePopoverActive(id)}
          >
            <div style={{ padding: "10px" }}>
              {groupedOptions.map((group, index) => (
                <div key={index} style={{ marginBottom: "10px" }}>
                  <strong>{group.title}</strong> {/* Category Name */}
                  <OptionList
                    onChange={(value) => handleSelectOptionChange(id, value, group.title)}
                    options={group.options}
                    selected={selectedOptions[id] || []}
                  />
                </div>
              ))}
            </div>
          </Popover>
        </IndexTable.Cell>
        <IndexTable.Cell>

          <Select
            key={JSON.stringify(carrierOptions)}
            options={carrierOptions[id]}
            // onChange={setSelectedCarrier}
            onChange={(value) => {
              if (value !== "") {
                handleCarrierChange(value, id);
              }
            }}
            value={carriers[id]?.label}
            disabled={label_url}
            placeholder="Select Carrier" />

        </IndexTable.Cell>
        <IndexTable.Cell>
          <Select
            disabled={groupTitle[id] !== "Local Shipping" || label_url}
            options={labelTemplateOptions}
            onChange={(value) => {
              if (value !== "") {
                // Use functional state update to set the selected label template for this id.
                setSelectedLabelTemplate((prev) => ({
                  ...prev,
                  [id]: parseInt(value, 10),
                }));
              }
            }}
            value={selectedLabelTemplate[id]}
            placeholder="Select Label Template"

          />
        </IndexTable.Cell>
        <IndexTable.Cell>
          <Select
            disabled={groupTitle[id] !== "Local Shipping" || label_url}
            options={Array.from(
              { length: Math.min(item_count, 5) },
              (_, index) => ({
                label: `${index + 1} Item${index + 1 > 1 ? "s" : ""}`,
                value: `${index + 1}`,
              })
            )}
            onChange={(value) => {
              if (value !== "") {
                handlePackageChange(value, id);
              }
            }}
            value={selectedPackage[id]}
          // disabled={label_url}
          />
        </IndexTable.Cell>
        <IndexTable.Cell>
          <Select
            options={boxOptions}
            onChange={(value) => { handleBoxChange(value, id) }}
            value={boxPackage[id]}
            disabled={label_url}
          />
        </IndexTable.Cell>
        <IndexTable.Cell>{customer_name}</IndexTable.Cell>
        <IndexTable.Cell>{item_count > 1
          ?
          `${item_count} Items`
          :
          `${item_count} Item`}</IndexTable.Cell>
        <IndexTable.Cell>{delivery_method}</IndexTable.Cell>
        <IndexTable.Cell>{total}</IndexTable.Cell>
        <IndexTable.Cell>
          <Popover
            active={popoverActiveAction[id] || false}
            activator={
              <Button onClick={() => togglePopoverActiveAction(id)} disclosure>
                Actions
              </Button>
            }
            onClose={() => togglePopoverActiveAction(id)}
          >
            <ActionList
              items={[
                // CASE 1: Non-Local Shipping 
                ...(groupTitle[id] !== "Local Shipping"
                  ? label_url
                    ? [
                      {
                        content: "Print Label",
                        onAction: () => handlePrintLabel(id),
                      },
                      ...(fullfilement !== "Fulfilled"
                        ? [
                          {
                            content: loadingOrders[id] ? "Processing..." : "Fulfill Order",
                            disabled: loadingOrders[id],
                            onAction: () => handleOrderFulfilled(id),
                          },
                        ]
                        : []),
                    ]
                    : [
                      {
                        content: "Get Shipping Rate",
                        onAction: () => handleGetShppingRates(id, otherCarrier[id], carriers[id]?.id, carriers[id]?.label, boxPackage[id]),
                      },
                    ]
                  : // CASE 2: Local Shipping 
                  label_url
                    ? [
                      {
                        content: "Print Label",
                        onAction: () => handlePrintLabel(id),
                      },
                      ...(fullfilement !== "Fulfilled"
                        ? [
                          {
                            content: loadingOrders[id] ? "Processing..." : "Fulfill Order",
                            disabled: loadingOrders[id],
                            onAction: () => handleOrderFulfilled(id),
                          },
                        ]
                        : []),
                    ]
                    : fullfilement === "Unfulfilled"
                      ? [
                        {
                          content: "Purchase Label",
                          onAction: () => handlePurchaseLabel(id, selectedOptions[id], carriers[id]?.id, carriers[id]?.label, selectedLabelTemplate[id], groupTitle[id], selectedPackage[id], boxPackage[id]),
                        },
                      ]
                      : []),
              ]}
            />
          </Popover>

        </IndexTable.Cell>

      </IndexTable.Row>
    ),
  );

  return (
    <>
    {!loader && <Loading />}
      <LegacyCard>
        <IndexFilters
          queryValue={queryValue}
          queryPlaceholder="Searching in all"
          onQueryChange={handleQueryValueChange}
          onQueryClear={() => setQueryValue('')}
          cancelAction={{}}
          tabs={tabs}
          canCreateNewView
          filters={filters}
          onClearAll={handleFiltersClearAll}
          mode={mode}
          setMode={setCurrent}
        />
        <IndexTable
          condensed={useBreakpoints().smDown}
          resourceName={resourceName}
          itemCount={orders.length}
          selectedItemsCount={
            allResourcesSelected ? 'All' : selectedResources.length
          }
          onSelectionChange={handleSelectionChange}
          headings={[
            { title: "Order" },
            { title: "Order Label ID" },
            { title: "Date" },
            { title: "Payment status" },
            { title: "Fulfillment status" },
            { title: "Delivery status" },
            { title: "Shipping Service" },
            { title: "Carrier Account" },
            { title: "Label Template" },
            { title: 'labels' },
            { title: 'Dimension' },
            { title: "Customer" },
            { title: "Items" },
            { title: "Delivery Service" },
            { title: "Total" },
            { title: "Action" },
          ]}
          // pagination={{
          //   hasPrevious: true,
          //   onPrevious: () => {
          //     prevPage();
          //   },
          //   hasNext: true,
          //   onNext: () => {
          //     nextPage();
          //   },
          // }}
          lastColumnSticky
          loading={!loader}
        >
          {loader && rowMarkup}
        </IndexTable>
        {orders.length > 0 && <Pagination
          hasPrevious
          onPrevious={() => {
            console.log('Previous');
            prevPage();
          }}
          hasNext
          onNext={() => {
            console.log('Next');
            nextPage();
          }}
        />}
      </LegacyCard>
      <RatesListingModal
        active={active}
        setActive={setActive}
        ratesData={ratesData}
        setRatesData={setRatesData}
        loading={loading}
        setLoading={setLoading}
        handleModalChange={handleModalChange}
        selectedOrderId={selectedOrderId}
        selectedserviceId={selectedserviceId}
        selectedCarrierId={selectedCarrierId}
        groupTitle={groupTitle}
        carriers={carriers}
        getOrderData={getOrderData}
      />
    </>
  );
}

export default CurrentOrder
