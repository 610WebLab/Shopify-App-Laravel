import React from 'react'
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import AddShipZone from '../components/shipzone/AddShipZone';
import ShippingZone from '../components/ShippingZone';
import TableRateTabs from '../components/tablerates/TableRateTabs';

const MenuRoutes = () => {
  return (
    <Routes>
      <Route exact path="/" element={<ShippingZone />} />
      <Route exact path="/new" element={<AddShipZone />} />
      <Route exact path="/new/:ZoneID" element={<AddShipZone />} />
      <Route exact path="/rate/:TableRateID" element={<TableRateTabs />} />
      {/* <Route path="/:id" element={<BlogDetails />} /> */}
    </Routes>
  )
}

export default MenuRoutes
