import React, { useState } from 'react';

function GeminiAIStudioGen() {
  const [prompt, setPrompt] = useState(
    'A vibrant, modern workspace with a laptop showing an upward growth chart, bright natural sunlight, clean minimalist aesthetic, corporate blue and white color palette, 4:5 aspect ratio.'
  );
  const [imageUrl, setImageUrl] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleGenerate = async () => {
    if (!prompt.trim()) return alert('দয়া করে একটি প্রম্পট লিখুন!');

    setLoading(true);
    setError(null);
    setImageUrl(null);

    // Google AI Studio API config
    const API_KEY = import.meta.env.VITE_GEMINI_API_KEY?.trim();
    if (!API_KEY) {
      setError('API কী পাওয়া যায়নি! সুবিধাজনকভাবে .env ফাইলে VITE_GEMINI_API_KEY সেট করুন।');
      setLoading(false);
      return;
    }

    // Google Vertex AI Imagen API endpoint for text-to-image (Imagen 2, public preview)
    // Docs: https://cloud.google.com/vertex-ai/docs/generative-ai/model-reference/image#text-to-image
    const url =
      'https://us-central1-aiplatform.googleapis.com/v1/projects/gemini-genai/locations/us-central1/publishers/google/models/imagegeneration:predict';

    const requestBody = {
      instances: [
        {
          prompt: prompt,
        }
      ],
      parameters: {
        sampleCount: 1,
        aspectRatio: '4:5', // Or '1:1', '16:9', '9:16', '4:3'
        mimeType: 'image/jpeg',
      },
    };

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${API_KEY}`,
          'x-goog-user-project': 'gemini-genai',
        },
        body: JSON.stringify(requestBody),
      });

      if (!response.ok) {
        const errorResponse = await response.json().catch(() => ({}));
        throw new Error(
          errorResponse?.error?.message ||
            `API রেসপন্স: ${response.status} (${response.statusText})`
        );
      }

      const data = await response.json();

      // Extract and display image
      // Response format: { predictions: [{ bytesBase64Encoded: '...' }] }
      if (
        Array.isArray(data?.predictions) &&
        data.predictions.length > 0 &&
        data.predictions[0].bytesBase64Encoded
      ) {
        const base64 = data.predictions[0].bytesBase64Encoded;
        setImageUrl(`data:image/jpeg;base64,${base64}`);
      } else {
        throw new Error('ছবি পাওয়া যায়নি! রেসপন্স ফরম্যাট চেক করুন অথবা আবার চেষ্টা করুন।');
      }
    } catch (err) {
      console.error('[GeminiAIStudioGen]', err);
      setError(err.message || 'সার্ভার কানেকশনে সমস্যা হয়েছে!');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      style={{
        maxWidth: '600px',
        margin: '40px auto',
        padding: '20px',
        textAlign: 'center',
        fontFamily: 'Arial, sans-serif',
      }}
    >
      <h2>Google AI Studio - Image Generator 🚀</h2>

      <textarea
        rows={3}
        value={prompt}
        onChange={(e) => setPrompt(e.target.value)}
        placeholder="যেমন: A beautiful sunset over Dhaka city, cinematic view..."
        style={{
          width: '100%',
          padding: '10px',
          marginBottom: '15px',
          borderRadius: '6px',
          border: '1px solid #ddd',
          resize: 'vertical',
        }}
      />

      <button
        onClick={handleGenerate}
        disabled={loading}
        style={{
          padding: '12px 24px',
          backgroundColor: loading ? '#ccc' : '#4285F4',
          color: '#fff',
          border: 'none',
          borderRadius: '5px',
          cursor: loading ? 'not-allowed' : 'pointer',
          fontSize: '16px',
        }}
      >
        {loading ? 'ছবি প্রসেস হচ্ছে...' : 'ছবি তৈরি করুন'}
      </button>

      {error && (
        <p style={{ color: 'red', marginTop: '15px' }}>
          ⚠️ {error}
        </p>
      )}

      {imageUrl && (
        <div style={{ marginTop: '25px' }}>
          <h3>আপনার জেনারেট করা ছবি:</h3>
          <img
            src={imageUrl}
            alt="AI Studio Generated"
            style={{
              width: '100%',
              borderRadius: '8px',
              boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            }}
          />
          <br />
          <a href={imageUrl} download="ai_studio_post.jpg">
            <button
              style={{
                marginTop: '10px',
                padding: '8px 16px',
                backgroundColor: '#34A853',
                color: '#fff',
                border: 'none',
                borderRadius: '4px',
                cursor: 'pointer',
              }}
            >
              ডাউনলোড করুন 💾
            </button>
          </a>
        </div>
      )}
    </div>
  );
}

export default GeminiAIStudioGen;
