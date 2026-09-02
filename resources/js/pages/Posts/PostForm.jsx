import React, { useState } from 'react';

export default function PostForm({
    socialAccounts,
    data,
    setData,
    errors,
    processing,
    onSubmit,
    submitLabel,
    processingLabel,
    existingMedia = [],
    onRemoveExistingMedia,
}) {
    const [previews, setPreviews] = useState([]);

    const handleAccountToggle = (id) => {
        const current = [...data.social_account_ids];
        const index = current.indexOf(id);
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(id);
        }
        setData('social_account_ids', current);
    };

    const handleMediaChange = (e) => {
        const files = Array.from(e.target.files);
        setData('media', files);

        const newPreviews = files.map((file) => URL.createObjectURL(file));
        setPreviews(newPreviews);
    };

    const previewImage =
        previews[0] || existingMedia[0] || null;

    return (
        <div className="max-w-7xl mx-auto py-10 px-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <form onSubmit={onSubmit} className="bg-white p-6 rounded-xl shadow border space-y-6">
                <h2 className="text-xl font-bold">{submitLabel}</h2>

                <div>
                    <label className="block text-sm font-medium mb-2">Target Accounts</label>
                    <div className="flex flex-wrap gap-2">
                        {socialAccounts.map((acc) => (
                            <button
                                key={acc.id}
                                type="button"
                                onClick={() => handleAccountToggle(acc.id)}
                                className={`px-3 py-1.5 rounded-lg border text-sm font-medium transition ${
                                    data.social_account_ids.includes(acc.id)
                                        ? 'bg-blue-50 border-blue-600 text-blue-600'
                                        : 'bg-white border-gray-200 text-gray-600'
                                }`}
                            >
                                {acc.name} ({acc.provider})
                            </button>
                        ))}
                    </div>
                    {errors.social_account_ids && (
                        <p className="text-red-500 text-xs mt-1">{errors.social_account_ids}</p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium mb-2">Content</label>
                    <textarea
                        rows={4}
                        value={data.content}
                        onChange={(e) => setData('content', e.target.value)}
                        className="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="What would you like to share?"
                    />
                    {errors.content && (
                        <p className="text-red-500 text-xs mt-1">{errors.content}</p>
                    )}
                </div>

                {existingMedia.length > 0 && (
                    <div>
                        <label className="block text-sm font-medium mb-2">Current Media</label>
                        <div className="flex flex-wrap gap-2">
                            {existingMedia.map((url) => (
                                <div key={url} className="relative">
                                    <img
                                        src={url}
                                        alt="Existing media"
                                        className="w-24 h-24 object-cover rounded-lg border"
                                    />
                                    {onRemoveExistingMedia && (
                                        <button
                                            type="button"
                                            onClick={() => onRemoveExistingMedia(url)}
                                            className="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full w-5 h-5"
                                        >
                                            ×
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <div>
                    <label className="block text-sm font-medium mb-2">Upload Media</label>
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        onChange={handleMediaChange}
                        className="w-full text-sm"
                    />
                    {errors.media && (
                        <p className="text-red-500 text-xs mt-1">{errors.media}</p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium mb-2">Schedule Date & Time</label>
                    <input
                        type="datetime-local"
                        value={data.scheduled_at}
                        onChange={(e) => setData('scheduled_at', e.target.value)}
                        className="w-full border-gray-200 rounded-lg"
                    />
                    <p className="text-xs text-gray-400 mt-1">Times are in your local timezone.</p>
                    {errors.scheduled_at && (
                        <p className="text-red-500 text-xs mt-1">{errors.scheduled_at}</p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition"
                >
                    {processing ? processingLabel : submitLabel}
                </button>
            </form>

            <div className="bg-gray-50 p-6 rounded-xl border flex flex-col items-center justify-center">
                <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">
                    Facebook Post Live Preview
                </h3>
                <div className="w-full max-w-sm bg-white rounded-xl shadow border overflow-hidden">
                    <div className="p-4 border-b flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-gray-200" />
                        <div>
                            <p className="font-semibold text-sm">Your Connected Page</p>
                            <p className="text-xs text-gray-400">Just now</p>
                        </div>
                    </div>
                    <div className="p-4">
                        <p className="text-sm text-gray-800 whitespace-pre-wrap">
                            {data.content || 'Your content preview will appear here...'}
                        </p>
                    </div>
                    {previewImage && (
                        <div className="grid grid-cols-1 gap-1 border-t">
                            <img
                                src={previewImage}
                                alt="Preview"
                                className="w-full h-48 object-cover"
                            />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
