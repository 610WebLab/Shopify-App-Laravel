import React,{useEffect, useState } from 'react';
import { Page, Frame } from '@shopify/polaris';
import MenuRoutes from '../routing/MenuRoutes';
import {useLocation } from "react-router-dom";

const App = () => {
  return (
    <Frame>
        <MenuRoutes />
    </Frame>
  )
}
export default App