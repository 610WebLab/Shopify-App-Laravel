import React, { lazy, Suspense } from 'react';
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";

// Lazy load components
const LocalShipping = lazy(() => import('../pages/LocalShipping'));
const AddShipZone = lazy(() => import('../components/shipzone/AddShipZone'));
const TableRateTabs = lazy(() => import('../components/tablerates/TableRateTabs'));
const SelectCarrierServices = lazy(() => import('../pages/OthersCarrierServices/SelectCarrierServices'));
const ListingOtherCarrierServices = lazy(() => import('../pages/OthersCarrierServices/ListingOtherCarrierServices'));
const AddEasyPost = lazy(() => import('../pages/OthersCarrierServices/easypost/AddEasyPost'));
const AddDistanceRates = lazy(() => import('../components/distance/AddDistanceRates'));
const OrderTabs = lazy(() => import('../pages/Order/OrderTabs'));
const SettingTabs = lazy(() => import('../pages/settings/SettingTabs'));
const TemplateCreateEditPage = lazy(() => import('../pages/settings/templates/TemplateCreateEditPage'));
const TemplateListPage = lazy(() => import('../pages/settings/templates/TemplateListPage'));

const DimensionListing = lazy(() => import('../pages/settings/dimension/DimensionListing'));
const CreateUpdateDimension = lazy(() => import('../pages/settings/dimension/CreateUpdateDimension'));
const BillingPage = lazy(() => import('../pages/BillingPage'));
const MenuRoutes = () => {
  return (
    <Suspense fallback={<div>Loading...</div>}>
      <Routes>
        <Route exact path="/" element={<LocalShipping />} />
        <Route exact path="/local-shipping" element={<LocalShipping />} />
        <Route exact path="/new" element={<AddShipZone />} />
        <Route exact path="/new/:ZoneID" element={<AddShipZone />} />
        <Route exact path="/rate/:TableRateID" element={<TableRateTabs />} />
        <Route exact path="/rate/:TableRateID" element={<TableRateTabs />} />
        <Route exact path="/distance/:DistanceID" element={<AddDistanceRates />} />
        {/* <Route exact path="/rate/:TableRateID" element={<TableRateTabs />} /> */}
        <Route exact path="/pages/add-other-careers" element={<SelectCarrierServices />} />
        <Route exact path="/pages/update-other-careers/:id/:carrier_type" element={<SelectCarrierServices />} />
        <Route exact path="/pages/other-careers" element={<ListingOtherCarrierServices />} />
        <Route exact path="/pages/orders" element={<OrderTabs />} />
        <Route exact path="/pages/settings" element={<SettingTabs />} />
        <Route exact path="/pages/templates" element={<TemplateListPage />} />
        <Route exact path="/pages/templates/create" element={<TemplateCreateEditPage />} />
        <Route exact path="/pages/templates/edit/:id" element={<TemplateCreateEditPage />} />
        <Route exact path="/pages/dimension" element={<DimensionListing />} />
        <Route exact path="/pages/dimension/create" element={<CreateUpdateDimension />} />
        <Route exact path="/pages/dimension/edit/:id" element={<CreateUpdateDimension />} />
        <Route exact path="/pages/plan" element={<BillingPage />} />

      </Routes>
    </Suspense>
  );
};

export default MenuRoutes;

