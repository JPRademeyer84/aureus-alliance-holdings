
// MySQL API logging for wallet connections
import ApiConfig from '@/config/api';

export async function logWalletConnection({
  provider,
  address,
  chainId
}: {
  provider: string;
  address: string;
  chainId: string;
}) {
  try {
    // Call MySQL API for logging wallet connection
    const response = await fetch(ApiConfig.endpoints.investments.process, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        event: 'wallet_connected',
        provider,
        address,
        chainId
      })
    });

    const data = await response.json();

    if (data.success) {
      console.log('Wallet connection logged successfully:', {
        provider,
        address,
        chainId,
        timestamp: new Date().toISOString()
      });
    } else {
      console.error('Failed to log wallet connection:', data.error);
    }
  } catch (e) {
    console.error("Failed to record wallet connection:", e);
  }
}
