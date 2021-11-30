// @ts-ignore
import countdown from 'countdown';

const DURATIONS = ['years', 'months', 'days', 'hours', 'minutes', 'seconds'] as const;

type Duration = typeof DURATIONS[number];
type Output = { duration: Duration; value: number };

const countdownWithPrecision = (from: Date, to: Date, precision: number): Output[] => {
    type Reduce = {
        output: Output[];
        diff: countdown.Timespan;
        precision: number;
    };

    const fn = ({ output, diff, precision }: Reduce, duration: Duration): Reduce => {
        if (precision && diff[duration]) {
            return {
                output: [...output, { value: diff[duration], duration } as Output],
                diff,
                precision: precision - 1,
            };
        }

        return { output, diff, precision };
    };

    const { output } = DURATIONS.reduce(fn, {
        output: [],
        diff: countdown(from, to),
        precision,
    } as Reduce);

    return output;
};

const toHtml = (output: Output[]): string =>
    output
        .map(
            ({ value, duration }) =>
                `<div class="countdown-section"><span class="countdown-amount">${value}</span>${duration}</div>`,
        )
        .join('');

const updateCountdown = (id: string, date: Date): void => {
    document.querySelector(id)!.innerHTML = toHtml(
        countdownWithPrecision(new Date(), date, 3),
    );
};

(window as any).initCountdown = (id: string, date: Date) => {
    setTimeout(updateCountdown, 3000, id, date);
    updateCountdown(id, date);
};
