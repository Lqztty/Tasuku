/**
 * ドラッグ中に「ここに置かれる」場所を示す点線の枠。
 * 持ち上げたカードと同じ高さにして、置いた後の見た目を想像しやすくする。
 */
export function DropPlaceholder({ height }: { height: number }) {
    return (
        <div
            aria-hidden
            style={{ height }}
            className="flex shrink-0 items-center justify-center rounded-lg border-2 border-dashed border-primary/60 bg-primary/5 text-xs font-medium text-primary"
        >
            ここに置きます
        </div>
    );
}
