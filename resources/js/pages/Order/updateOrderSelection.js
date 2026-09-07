import axios from "axios";

/**
 * Persist order dropdown selections to the database.
 * @param {number|string} orderId
 * @param {Object} fields
 * @param {{ shop?: string, reset_carrier?: boolean }} options
 * @returns {Promise<{ status: boolean, message?: string, data?: object }>}
 */
export const updateOrderSelection = async (orderId, fields = {}, options = {}) => {
  if (!orderId) {
    return { status: false, message: "Order ID is required" };
  }

  const shop = options.shop || (typeof Config !== "undefined" ? Config.shop : null);
  if (!shop) {
    return { status: false, message: "Shop is required" };
  }

  const payload = {
    shop,
    _token: typeof Config !== "undefined" ? Config.csrf_token : undefined,
    ...fields,
  };

  if (Object.prototype.hasOwnProperty.call(options, "reset_carrier")) {
    payload.reset_carrier = options.reset_carrier ? 1 : 0;
  }

  const response = await axios.put(`/orders/${orderId}`, payload);

  return response.data;
};

export default updateOrderSelection;
