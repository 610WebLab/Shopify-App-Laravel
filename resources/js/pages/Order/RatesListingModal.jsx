import React, { useState } from "react";
import {
  Button,
  Frame,
  Modal,
  ResourceList,
  ResourceItem,
  RadioButton,
  TextContainer,
  Toast
} from "@shopify/polaris";
import axios from "axios";
import { useToast } from '@shopify/app-bridge-react';
export default function RatesListingModal({
  active,
  setActive,
  ratesData,
  loading,
  handleModalChange,
  selectedOrderId,
  selectedserviceId,
  selectedCarrierId,
  getOrderData,
  carriers,
  groupTitle
}) {
  const { show } = useToast();
  const [rateID, setRateID] = useState(null);
  const [shipmentId, setShipmentId] = useState(null);
  const [ratePrice, setRatePrice] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);
  const [toastError, setToastError] = useState(false);

  // Show Toast Message
  const showToast = (message, isError = false) => {
    setToastMessage(message);
    setToastError(isError);
    setTimeout(() => setToastMessage(null), 3000);
  };

  // Create Shipping Label with Field-by-Field Validation
  const createShippingLabel = async () => {
    if (!selectedOrderId) {
      show("Order ID is required!", { duration: 2000, isError: true });
      return false;
    }
    if (!selectedserviceId) {
      show("Service ID is required!", { duration: 2000, isError: true });
      return false;
    }
    if (!selectedCarrierId) {
      show("Carrier ID is required!", { duration: 2000, isError: true });
      return false;
    }
    if (!rateID) {
      show("Please select a shipping rate!", { duration: 2000, isError: true });
      return false;
    }
    if (!shipmentId) {
      show("Shipment ID is required!", { duration: 2000, isError: true });
      return false;
    }
    if (!ratePrice) {
      show("Rate price is required!", { duration: 2000, isError: true });
      return false;
    }

    const requestData = {
      order_id: selectedOrderId,
      service_id: selectedserviceId,
      carrier_id: selectedCarrierId,
      shipment_id: shipmentId,
      rate_id: rateID,
      rate_price: ratePrice,
      group_title: groupTitle[selectedOrderId],
      carrier_label:carriers[selectedOrderId]?.label
    };

    console.log("Sending Data:", requestData);
    setIsSubmitting(true);

    try {
      const response = await axios.post("/create-shipping-label", requestData);

      if (response.data.success) {
        show(response.data.message, { duration: 2000 });
        handleModalChange();
        getOrderData()
      } else {
        show(response.data.message || "Failed to create shipping label.", { duration: 2000, isError: true });
      }
    } catch (error) {
      console.error("Error creating shipping label:", error);

      if (error.response) {
        // API responded with a specific error
        const errorMessage = error.response.data.message || "Failed to create shipping label.";
        show(errorMessage, { duration: 2000, isError: true });
      } else {
        show("Network error. Please try again.", { duration: 2000, isError: true });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <Modal
        open={active}
        onClose={handleModalChange}
        title="Select a Shipping Rate"
        primaryAction={{
          content: isSubmitting ? "Creating..." : "Create Label",
          onAction: createShippingLabel,
          disabled: isSubmitting,
        }}
        secondaryActions={[{ content: "Cancel", onAction: handleModalChange }]}
      >
        <Modal.Section>
          {loading ? (
            <TextContainer>Loading rates...</TextContainer>
          ) : ratesData?.length > 0 ? (
            <ResourceList
              resourceName={{ singular: "rate", plural: "rates" }}
              items={ratesData}
              renderItem={(rate) => (
                <ResourceItem id={rate.id} accessibilityLabel={`Select ${rate.service}`}>
                  <RadioButton
                    label={`${rate.service} - $${rate.rate} (Delivery: ${rate.delivery_days} days)`}
                    checked={rateID === rate.id}
                    onChange={() => {
                      setRateID(rate.id);
                      setShipmentId(rate.shipment_id);
                      setRatePrice(rate.rate);
                    }}
                  />
                </ResourceItem>
              )}
            />
          ) : (
            <TextContainer>No shipping rates available.</TextContainer>
          )}
        </Modal.Section>
      </Modal>
    </div>
  );
}
