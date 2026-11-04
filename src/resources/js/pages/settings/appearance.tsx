import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="外観" />

            <h1 className="sr-only">外観</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="外観"
                    description="テーマ（ライト・ダーク）を切り替えます"
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: '外観',
            href: editAppearance(),
        },
    ],
};
