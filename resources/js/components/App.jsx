import React from 'react'
import { Page, Frame } from '@shopify/polaris';
import MenuRoutes from '../routing/MenuRoutes';

const App = () => {
  return (
    <Frame>
        <MenuRoutes />
    </Frame>
  )
}
export default App