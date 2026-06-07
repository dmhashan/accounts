import { StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

import { Card } from '../components/Card';
import { EmptyState } from '../components/EmptyState';
import { Screen } from '../components/Screen';
import { colors, radius } from '../theme';
import type { Sale, WalletTransaction } from '../types/profile';
import { money } from '../utils/format';

type Props = {
  sales: Sale[];
  walletTransactions: WalletTransaction[];
};

export function TransactionsScreen({ sales, walletTransactions }: Props) {
  return (
    <Screen>
      <Text style={styles.title}>Transactions</Text>
      <Text style={styles.subtitle}>{sales.length} payments · {walletTransactions.length} wallet entries</Text>

      {!sales.length && !walletTransactions.length ? <EmptyState icon="receipt-outline" text="No transactions yet" /> : null}

      {sales.length ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Payments</Text>
          {sales.map((sale) => (
            <Card key={String(sale.id)}>
              <View style={styles.saleHead}>
                <View style={styles.iconBox}>
                  <Ionicons name="receipt-outline" size={19} color={!sale.is_paid ? colors.danger : colors.muted} />
                </View>
                <View style={styles.saleCopy}>
                  <Text style={styles.saleTitle}>Invoice #{sale.id}</Text>
                  <Text style={styles.saleMeta}>{sale.created_at || '-'}{sale.payment_method ? ` · ${sale.payment_method}` : ''}</Text>
                </View>
                <Text style={[styles.badge, sale.is_paid ? styles.paid : styles.unpaid]}>{sale.is_paid ? 'Paid' : 'Unpaid'}</Text>
              </View>

              <View style={styles.amountGrid}>
                <Amount label="Total" value={money(sale.total_amount)} />
                <Amount label="Paid" value={money(sale.paid_amount)} />
                <Amount label="Balance" value={money(sale.balance)} />
              </View>

              {sale.items?.map((item, index) => (
                <View key={`${item.product_name}-${index}`} style={styles.itemRow}>
                  <Text style={styles.itemName}>{item.product_name || '-'}</Text>
                  <Text style={styles.itemAmount}>{money(item.subtotal)}</Text>
                </View>
              ))}
            </Card>
          ))}
        </View>
      ) : null}

      {walletTransactions.length ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Wallet</Text>
          <Card>
            {walletTransactions.map((tx, index) => (
              <View key={String(tx.id ?? index)} style={styles.walletRow}>
                <View style={styles.walletCopy}>
                  <Text style={styles.walletTitle}>{tx.description || tx.type || 'Wallet transaction'}</Text>
                  <Text style={styles.walletDate}>{tx.created_at || '-'}</Text>
                </View>
                <Text style={styles.walletAmount}>{money(tx.amount)}</Text>
              </View>
            ))}
          </Card>
        </View>
      ) : null}
    </Screen>
  );
}

function Amount({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.amount}>
      <Text style={styles.amountLabel}>{label}</Text>
      <Text style={styles.amountValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  title: {
    color: colors.ink,
    fontSize: 27,
    fontWeight: '900'
  },
  subtitle: {
    color: colors.faint,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 4,
    marginBottom: 18
  },
  section: {
    gap: 12,
    marginBottom: 20
  },
  sectionTitle: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900'
  },
  saleHead: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12
  },
  iconBox: {
    width: 38,
    height: 38,
    borderRadius: radius.md,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center'
  },
  saleCopy: {
    minWidth: 0,
    flex: 1
  },
  saleTitle: {
    color: colors.ink,
    fontWeight: '900',
    fontSize: 15
  },
  saleMeta: {
    color: colors.faint,
    fontWeight: '700',
    fontSize: 11,
    marginTop: 3
  },
  badge: {
    overflow: 'hidden',
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
    fontSize: 10,
    fontWeight: '900'
  },
  paid: {
    color: colors.success,
    backgroundColor: colors.successSoft
  },
  unpaid: {
    color: colors.danger,
    backgroundColor: colors.dangerSoft
  },
  amountGrid: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 16,
    marginBottom: 8
  },
  amount: {
    flex: 1,
    borderRadius: radius.md,
    backgroundColor: '#f9fafb',
    padding: 10
  },
  amountLabel: {
    color: colors.faint,
    fontSize: 10,
    fontWeight: '900',
    marginBottom: 4
  },
  amountValue: {
    color: colors.ink,
    fontSize: 13,
    fontWeight: '900'
  },
  itemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
    paddingTop: 10,
    marginTop: 8
  },
  itemName: {
    minWidth: 0,
    flex: 1,
    color: colors.text,
    fontSize: 13,
    fontWeight: '700'
  },
  itemAmount: {
    color: colors.ink,
    fontSize: 13,
    fontWeight: '900'
  },
  walletRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    paddingVertical: 10
  },
  walletCopy: {
    minWidth: 0,
    flex: 1
  },
  walletTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800'
  },
  walletDate: {
    color: colors.faint,
    fontSize: 11,
    fontWeight: '700',
    marginTop: 3
  },
  walletAmount: {
    color: colors.ink,
    fontSize: 14,
    fontWeight: '900'
  }
});
